<?php
global $LANG;

header("Content-type: text/xml");

// @formatter:off
$dir  = (isset($_POST['sortorder'])) ? $_POST['sortorder'] : "DESC" ;
$sort = (isset($_POST['sortname']) ) ? $_POST['sortname']  : "ap.id" ;
$rp   = (isset($_POST['rp'])       ) ? $_POST['rp']        : "25" ;
$page = (isset($_POST['page'])     ) ? $_POST['page']      : "1" ;
// @formatter:on

function sql($type = '', $dir, $sort, $rp, $page) {
    global $auth_session;

    if (!preg_match('/^(asc|desc)$/iD', $dir)) $dir = 'DESC';

    // SQL Limit - start
    $start = (($page-1) * $rp);
    $limit = "LIMIT $start, $rp";

    if ($type == "count") $limit = "";
    // SQL Limit - end

    $where = "";
    $query = isset($_POST['query']) ? $_POST['query'] : null;
    $qtype = isset($_POST['qtype']) ? $_POST['qtype'] : null;
    if ( ! (empty($qtype) || empty($query)) ) {
        if ( in_array($qtype, array('ap.id','b.name', 'c.name')) ) {
            $where = " AND $qtype LIKE :query ";
        } else {
            $qtype = null;
            $query = null;
        }
    }

    // Check that the sort field is OK
    $validFields = array('ap.id', 'ap.ac_inv_id', 'description');

    if (in_array($sort, $validFields)) {
        $sort = $sort;
    } else {
        $sort = "ap.id";
    }
    // @formatter:off
    $sql = "SELECT ap.*
                 , c.name as cname
                 , (SELECT CONCAT(pr.pref_inv_wording,' ',iv.index_id)) as index_name
                 , b.name as bname
                 , pt.pt_description AS description
                 , ac_notes AS notes
                 , DATE_FORMAT(ac_date,'%Y-%m-%d') AS date
            FROM ".TB_PREFIX."payment ap
            INNER JOIN ".TB_PREFIX."invoices iv      ON (ap.ac_inv_id = iv.id AND ap.domain_id = iv.domain_id)
            INNER JOIN ".TB_PREFIX."customers c      ON (c.id = iv.customer_id AND c.domain_id = iv.domain_id)
            INNER JOIN ".TB_PREFIX."biller b         ON (b.id = iv.biller_id AND b.domain_id = iv.domain_id)
            INNER JOIN ".TB_PREFIX."preferences pr   ON (pr.pref_id = iv.preference_id AND pr.domain_id = ap.domain_id)
            INNER JOIN ".TB_PREFIX."payment_types pt ON (pt.pt_id = ap.ac_payment_type AND pt.domain_id = ap.domain_id)
            WHERE ap.domain_id = :domain_id ";
    // @formatter:on

    // if coming from another page where you want to filter by just one invoice
    if (!empty($_GET['id'])) {
        $id = $_GET['id'];
        $sql .= " AND ap.ac_inv_id = :invoice_id $where ORDER BY $sort $dir $limit";
        if (empty($query)) {
            $result = dbQuery($sql, ':domain_id', $auth_session->domain_id, ':invoice_id', $id);
        } else {
            $result = dbQuery($sql, ':domain_id', $auth_session->domain_id, ':invoice_id', $id, ':query', "%$query%");
        }
    } elseif (!empty($_GET['c_id'])) {
        $id = $_GET['c_id'];
        $sql .= " AND c.id = :id ORDER BY $sort $dir $limit";
        $result = dbQuery($sql, ':id', $id, ':domain_id', $auth_session->domain_id);
    } else {
        $sql .= "$where ORDER BY $sort $dir $limit";
        if (empty($query)) {
            $result =  dbQuery($sql, ':domain_id', $auth_session->domain_id);
        } else {
            $result =  dbQuery($sql, ':domain_id', $auth_session->domain_id, ':query', "%$query%");
        }
    }
    return $result;
}

$sth = sql('', $dir, $sort, $rp, $page);
$sth_count_rows = sql('count',$dir, $sort, $rp, $page);

$payments = $sth->fetchAll(PDO::FETCH_ASSOC);
$count    = $sth_count_rows->rowCount();

// @formatter:off
  $xml  = "";
  $xml .= "<rows>";
  $xml .= "<page>$page</page>";
  $xml .= "<total>$count</total>";
  
  foreach ($payments as $row) {
    $notes = si_truncate($row['notes'],'13','...');
    $xml .= "<row id='".$row['id']."'>";
    $xml .= "<cell><![CDATA[
                <a class='index_table' title='$LANG[view] $row[name]'
                   href='index.php?module=payments&view=details&id=$row[id]&action=view'>
                  <img src='images/common/view.png' height='16' border='-5px' padding='-4px' valign='bottom' />
                </a>
                <a class='index_table' title='$LANG[print_preview_tooltip] $row[id]'
                   href='index.php?module=payments&view=print&id=$row[id]'>
                  <img src='images/common/printer.png' height='16' border='-5px' padding='-4px' valign='bottom' />
                </a>
                ]]></cell>";

    $xml .= "<cell><![CDATA[".$row['id']."]]></cell>";
    $xml .= "<cell><![CDATA[".$row['index_name']."]]></cell>";
    $xml .= "<cell><![CDATA[".$row['cname']."]]></cell>";
    $xml .= "<cell><![CDATA[".$row['bname']."]]></cell>";
    $xml .= "<cell><![CDATA[".siLocal::number($row['ac_amount'])."]]></cell>";
    $xml .= "<cell><![CDATA[".$notes."]]></cell>";
    $xml .= "<cell><![CDATA[".$row['description']."]]></cell>";
    $xml .= "<cell><![CDATA[".siLocal::date($row['date'])."]]></cell>";
    $xml .= "</row>";
  }
  $xml .= "</rows>";
// @formatter:on

echo $xml;
