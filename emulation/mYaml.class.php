<?php
//-------------------------------------------------------------------------------------------------
define("KYP_OK",1);
define("KYP_ERROR_SYNTAX",-1);
define("KYP_ERROR_FILE",-2);
//-------------------------------------------------------------------------------------------------

class KobeYamlParser
{
    private $errno;
    private $lineno;
    //----------------------------------------------
    public function GetErrno()
    {
        return $this->errno;
    }
    public function GetErrmsg($errno)
    { 
        switch($errno)
        {
            case KYP_ERROR_SYNTAX:
                return 'yaml syntax error';
            case KYP_ERROR_FILE:
                return 'yaml read error';
        }
        return '';
    }
    public function GetErrorLineno()
    {
        return $this->lineno;
    }
    //----------------------------------------------
    public function ParseString($string)
    {
        $this->errno=KYP_OK;
        $this->lineno=0;
        $result=false;
        $pos=0;
        $pos2=0;
        $line_index=0;
        $lastkey='';
        $lastarray=null;
        while($pos<strlen($string))
        {
            $res=$this->GetLine($string,$pos,$pos2);
            $line_index++;
            if(ltrim($res)==''||trim($res)=='---'||trim($res)=='...')
            {
                $pos=$pos2;
                continue;
            }
            $level=1;
            $s=0;
            $key="";
            $value="";
            if($this->ParseLineInfor($res,$level,$s,$key,$value)==false)
            {
                $this->errno=KYP_ERROR_SYNTAX;
                $this->lineno=$line_index;
                return false;
            }
            if($level==1)
            {
                if($lastarray!=null)
                {
                    array_push($result[$lastkey],$lastarray);
                    $lastarray=null;
                }
                if($value!="")
                {
                    $result[$key]=$value;
                    $lastkey="";
                }
                else
                {
                    $result[$key]=array();
                    $lastkey=$key;
                }
            }
            else if($level==2)
            {
                if($lastkey=="")
                {
                    $this->errno=KYP_ERROR_SYNTAX;
                    $this->lineno=$line_index;
                    return false;
                }
                if($s==1)
                {
                    if($lastarray!=null)
                    {
                        array_push($result[$lastkey],$lastarray);
                        $lastarray=null;
                    }
                    $lastarray=array();
                    $lastarray[$key]=$value;
                }
                else
                    $lastarray[$key]=$value;
            }
            if($pos2>=strlen($string))
                break;
            $pos=$pos2;
        }//end while
        if($lastarray!=null)
        {
            array_push($result[$lastkey],$lastarray);
            $lastarray=null;
        }
        return $result;
    }
    public function ParseFile($filename)
    {
        if(file_exists($filename)==false)
        {
            $this->errno=KYP_ERROR_FILE;
            return false;
        }
        return $this->ParseString(file_get_contents($filename));
    }
    private function ParseLineInfor($line,&$level,&$s,&$key,&$value)
    {
        $i=0;
        while($line[$i]==' '||$line[$i]=="\t")
            $i++;
        if($i>=strlen($line))
            return false;
        if($i>0)
            $level=2;
        else
            $level=1;
        if($line[$i]=='-')
        {
            $s=1;
            $i++;
            $level=2;
        }
        else
            $s=0;
        $p=strpos($line,':',$i);
        if($p==false)
            return false;
        $key=trim(substr($line,$i,$p-$i));
        $value=trim(substr($line,$p+1));
        return true;
    }
    private function GetLine($string,$start,&$end)
    {
        $pos=$start;
        $length=strlen($string);
        $undertc=false;
        for(;$pos<$length;)
        {
            if($string[$pos]=="\n")
            {
                $pos++;
                $end=$pos;
                return substr($string,$start,$end-$start);
            }
            else if($string[$pos]=="'"||$string[$pos]=='"')
            {
                $st=$string[$pos];
                $pos++;
                for(;$pos<$length;)
                {
                    if($string[$pos]=='\n')
                        break;
                    else if($undertc)
                        $undertc=false;
                    else if($string[$pos]=="\\")
                        $undertc=true;
                    else if($string[$pos]==$st)
                    {
                        $pos++;
                        break;
                    }
                    $pos++;
                }
                continue;
            }
            else if($string[$pos]=='#')
            {
                $rend=$pos;
                $pos++;
                for(;$pos<$length&&$string[$pos]!="\n";$pos++);
                if($pos<$length)
                    $pos++;
                $end=$pos;
                return substr($string,$start,$rend-$start);
            }
            else
                $pos++;
        }//end for
        $end=$pos;
        return substr($string,$start,$end-$start);
    }//end function
    //----------------------------------------------
    public function OutputFromArray($array)
    {
        $res='';
        if(is_array($array)==false)
            return false;
        foreach($array as $key => $value)
        {
            $res.=$key.':';
            if(is_array($value)==false)
            {
                $res.=' '.$value."\n";
                continue;
            }
            else
            {
                $res.="\n";
                foreach($value as $item)
                {
                    if(is_array($item)==false)
                        continue;
                    $first=true;
                    foreach($item as $k => $v)
                    {
                        if($first)
                        {
                            $res.='- '.$k.': '.$v."\n";
                            $first=false;
                        }
                        else
                            $res.='  '.$k.': '.$v."\n";
                    }
                }
            }
        }//end foreach
        return $res;
    }
};//end class KobeYamlParser

//-------------------------------------------------------------------------------------------------
?>