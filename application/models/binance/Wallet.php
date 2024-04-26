<?php

class Wallet 
{
    protected $db;
    protected $errLog;

    protected $api;
    protected $idusuario;

    function __Construct($api=null,$idusuario)
    {
        $this->db = DB::getInstance();
        $this->errLog = new ErrorLog();

        $this->api = $api;
        $this->idusuario = $idusuario;
    }

    function update()
    {
        $total = $this->api->walletTotal();
        
        if ($total > 0)
        {
            $total = toDec($total);
            $date = date('Y-m-d');
            $qry = "SELECT * 
                    FROM wallet 
                    WHERE date = '".$date."' AND idusuario = '".$this->idusuario."'";
            $stmt = $this->db->query($qry);
            $rw = $stmt->fetch();

            if (empty($rw))
            {
                $ins = "INSERT INTO wallet (idusuario,date,open,high,low,close) VALUES (
                        '".$this->idusuario."',
                        '".$date."',
                        '".$total."',
                        '".$total."',
                        '".$total."',
                        '".$total."'
                        )";
                $this->db->query($ins);
            }
            else
            {
                $high = ($total>$rw['high'] ? $total : $rw['high']);
                $low = ($total<$rw['low'] ? $total : $rw['low']);
                $close = $total;

                $upd = "UPDATE wallet SET
                        high = '".$high."',
                        low = '".$low."',
                        close = '".$close."'
                        WHERE date = '".$date."' AND idusuario  = '".$this->idusuario."' ";
                $this->db->query($upd);
            }
        }
    }

    function get($periodo)
    {
        $addWhere = '';
        if ($periodo > 0)
            $addWhere = " AND date >= '".date('Y-m-d',strtotime('-'.$periodo.' days'))." 00:00:00' ";

        $qry = 'SELECT * FROM wallet WHERE idusuario = '.$this->idusuario.' '.$addWhere.' ORDER BY date';
        $stmt = $this->db->query($qry);
        return $stmt->fetchAll();
    }

}