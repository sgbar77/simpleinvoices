<?php

class payment
{

    public $start_date;
    public $end_date;
    public $filter;

    function select_all() {
        global $auth_session;
        
        if($this->filter == "date")
        {
            $where = "and ap.ac_date between '$this->start_date' and '$this->end_date'";
        }

        $sql = "SELECT 
                    ap.*, 
                    iv.index_id as index_id,
                    iv.id as invoice_id,
                    pref.pref_description as preference,
                    c.name as cname, 
                    b.name as bname 
                from 
                    ".TB_PREFIX."payment ap, 
                    ".TB_PREFIX."invoices iv, 
                    ".TB_PREFIX."customers c, 
                    ".TB_PREFIX."biller b,
                    ".TB_PREFIX."preferences pref
                WHERE 
                    ap.ac_inv_id = iv.id 
                    AND 
                    iv.customer_id = c.id 
                    and 
                    iv.biller_id = b.id 
                    and 
                    iv.preference_id = pref.pref_id
                    and
                    ap.domain_id = :domain_id
                    $where
                ORDER BY ap.id DESC";
        
        return dbQuery($sql,':domain_id',$auth_session->domain_id);
    }

}
