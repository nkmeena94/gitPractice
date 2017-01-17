<?php 
//{"type":"all","parameters":[{"type":"Anniversary","condition":"is after","param":"7 days"}]}

?>

<?php

  function isJson($string) {
        if(!is_string($string)) return false;

        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

      function getActionValues($name){
        $values = explode(" ",$name);

        // if(strtolower($values[1]) == 'call') $cause = 'call';
        // else $cause = 'checkin';
        $cause = "";
        if(isset($values[1])){
        $cause = $values[1];
        if(strtolower($values[1]) == 'order') $cause = 'checkin';
        if(strtolower($values[1]) == 'reservation' && strtolower($values[2]) == 'confirm') $cause = 'reservationconfirm'; 
        }
        $channel = $values[0];
        
        if(strtolower($values[0]) == 'delivery') $channel = 'call';
        
        $result['earnburn_cause'] = $cause;
        $result['channel'] = $channel;
        return $result;
    }

   function getMembers($segment_id) {
        //$DB = $this->DB;
         $DB = mysqli_connect("localhost","root","NKpassword@94","zenmaster");
        $stmt = $DB->prepare('SELECT `customer_id` FROM `filter_map` where 
            `filter_id` = ?');
        $stmt->bind_result($uid);
        $stmt->bind_param('i', $segment_id);
        $stmt->execute();

        $uids = array();
        while($stmt->fetch()) {
            $uids[] = $uid;
        }
        $stmt->close();

        return $uids;
    }


    function getInverseMembers($pid , $segment_id) {
       // $program = new Program($this->data['program_id']);
        $all     = getUserIDs($pid);
        $members = getMembers($segment_id);
        $inverse = array_diff($all, $members);

        return $inverse;
    }


    function getUserIDs($pid) {

        //if(!isset($this->db_exists)) return false;
        //$DB = $this->DB;
         $DB = mysqli_connect("localhost","root","NKpassword@94","zenmaster");
        
        $stmt = $DB->prepare('SELECT `customer_id` from `customers_merchant_map` where `is_active` <> 0 and `merchant_id` = ?');
        $stmt->bind_param('i', $pid);
        $stmt->bind_result($uid);
        $stmt->execute();

        $uids = array();
        while($stmt->fetch()) {
            $uids[] = $uid;
        }
        $stmt->close();

        return $uids;
    }
function getReverseTranslationFromRewardName($program_id, $name) {
       // $DB = DB::getInstance();
        $DB = mysqli_connect("localhost","root","NKpassword@94","zenmaster");
        
        $stmt = $DB->prepare('SELECT `id` from `rewards` where `program_id` = ? and `name` = ?');
        $stmt->bind_param('is', $program_id, $name);
        $stmt->bind_result($rid);
        $stmt->execute();
        $stmt->fetch();

        if(!isset($rid) || !is_numeric($rid)) return false;
        else return $rid;
    }

//$json = '{"type":"all","parameters":[{"type":"Anniversary","condition":"is after","param":"7 days"}]}';

$json = '{"type":"all","parameters":[{"type":"First Visit","condition":"was","param":"14 days ago"},{"type":"no. of visits","condition":"are","param":1}]}';

$result =getUsersFromTriggerRule($json  , 3);
echo "size of Result: ".sizeof($result)."\n\n"; 

 function  getUsersFromTriggerRule($filter_string , $id) {
    
        $DB = mysqli_connect("localhost","root","NKpassword@94","zenmaster");
      // $DB = $this->DB;
        //$query = 'SELECT `user_id` FROM  `users_program_map` WHERE `is_active` <> 0 and `numer_of_visits` > 0 and `program_id` = '.$this->id;

        $query =  'SELECT `customer_id` FROM  `customers_merchant_map`  WHERE `is_active` <> 0 and `numer_of_visits` > 0 and `merchant_id` ='.$id;;

        $first = true;
        if(isset($filter_string) && $filter_string != '') {
            $filter = json_decode($filter_string);
            if(isset($filter->parameters) && count($filter->parameters) > 0) {
                //error_log('param here');
                $is    = null;
                $run   = 0;
                $type  = $filter->type;
                $join  = ($filter->type == 'any')?' OR ':' AND ';
                $count = count($filter->parameters);
                $sbid  = null;
                //error_log(json_encode($filter->parameters));

                foreach ($filter->parameters as $rule) {
                    $run++;
                    $rule->type = strtolower($rule->type);

                    if($first) {
                        if($rule->type != "birthday" && $rule->type != "anniversary") {
                            $query .= ' and (';
                            $first = false;
                        }
                    }
                    else if($rule->type != "birthday" && $rule->type != "anniversary") $query .= $join;

                    if($rule->type == 'last visit') {
                        $rule->param = str_replace('days ago', '', $rule->param);

                        $condition = null;
                        if($rule->condition == 'was') $condition = '=';
                        
                        if(isset($rule->param) && isset($condition)){
                            if($rule->condition == 'was'){
                               $query .= 'DATE(`last_visited`)'.$condition.' DATE_SUB(CURDATE(), INTERVAL '.$rule->param.' DAY)';
                            }
                        }
                        
                    }
                    else if($rule->type == 'first visit') {
                        $rule->param = str_replace('days ago', '', $rule->param);

                        $condition = null;
                        if($rule->condition == 'was') $condition = '=';
                       
                        if(isset($rule->param) && isset($condition)){
                            if($rule->condition == 'was'){
                               $query .= 'DATE(`created_on`)'.$condition.'DATE_SUB(CURDATE(), INTERVAL '.$rule->param.' DAY)';
                            }
                        }
                    }else if($rule->type == 'no. of visits') {
                        $param = null;
                        if(is_numeric($rule->param)) $param = $rule->param;

                        $condition = null;
                        if($rule->condition == 'are') $condition = '=';
                        
                        if(isset($param) && isset($condition))
                        $query .= '(`numer_of_visits` '.$condition.' '.$param.')';
                    }
                    else if($rule->type == 'birthday') {
                        
                        
                        if($rule->condition == 'is after') {
                            $condition = '=';
                            $rule->param = str_replace('days', '', $rule->param);
                        }else if($rule->condition == 'was'){
                            $condition = '=';
                            $rule->param = str_replace('days ago', '', $rule->param);
                        }else if ($rule->condition == "is today") {
                            $condition = '=';
                        }


                        $birthday_query = 'SELECT `id` FROM `customers` WHERE DATE_ADD(`birthday`, INTERVAL YEAR(CURDATE())-YEAR(`birthday`) YEAR) ';
                        if($rule->condition == 'is after'){
                            $birthday_query = $birthday_query. $condition . ' DATE_ADD(CURDATE(), INTERVAL ' .$rule->param.' DAY )';
                        }else if($rule->condition == "was"){
                            $birthday_query = $birthday_query. $condition . ' DATE_SUB(CURDATE(), INTERVAL ' .$rule->param.' DAY )';
                        }else if($rule->condition == "is today"){
                            $birthday_query = $birthday_query. $condition . ' CURDATE()';
                        }
                        $stmt = $DB->prepare($birthday_query);
                        $stmt->bind_result($birthday_uid);
                        $stmt->execute();

                        $birthday_uids = array();
                        while($stmt->fetch()) {
                            $birthday_uids[] = $birthday_uid;
                        }
                        $stmt->close();
                        $stmt = $DB->prepare('SELECT `customer_id` from `customers_merchant_map` where `is_active` <> 0 and `merchant_id` = ?');

                        $stmt->bind_param('i', $id);
                        $stmt->bind_result($uid);
                        $stmt->execute();

                        $uids = array();
                        while($stmt->fetch()) {
                            $uids[] = $uid;
                        }
                        $stmt->close();

                        $uids = array_intersect($birthday_uids, $uids);

                        if(!isset($is)) $is = $uids;
                        else if($type == 'any') $is = array_unique(array_merge($is, $uids));
                        else $is = array_intersect($is, $uids);
                    }



                    else if($rule->type == 'anniversary') {
                        
                        
                        if($rule->condition == 'is after') {
                            $condition = '=';
                            $rule->param = str_replace('days', '', $rule->param);
                        }else if($rule->condition == 'was'){
                            $condition = '=';
                            $rule->param = str_replace('days ago', '', $rule->param);
                        }else if ($rule->condition == "is today") {
                            $condition = '=';
                        }

                        $anniversary_query = 'SELECT `id` FROM `customers` WHERE DATE_ADD(`anniversary`, INTERVAL YEAR(CURDATE())-YEAR(`anniversary`) YEAR)' ;
                        if($rule->condition == 'is after'){
                            $anniversary_query = $anniversary_query. $condition . ' DATE_ADD(CURDATE(), INTERVAL ' .$rule->param.' DAY )';
                        }else if($rule->condition == "was"){
                            $anniversary_query = $anniversary_query. $condition . ' DATE_SUB(CURDATE(), INTERVAL ' .$rule->param.' DAY )';
                        }else if($rule->condition == "is today"){
                            $anniversary_query = $anniversary_query. $condition . ' CURDATE()';
                        }
                        echo "\n anniversary Query \n";
                        echo $anniversary_query."\n";
                        $stmt = $DB->prepare($anniversary_query);
                        $stmt->bind_result($anniversary_uid);
                        $stmt->execute();

                        $anniversary_uids = array();
                        while($stmt->fetch()) {
                            $anniversary_uids[] = $anniversary_uid;
                        }

                        echo "anniversary_uids size ".sizeof($anniversary_uids)."\n";
                        $stmt->close();

                        echo "anniversary__UID size " . sizeof($anniversary_uids)."\n";
                         // echo "anniversary IS size " . sizeof($is)."\n";

                        $stmt = $DB->prepare('SELECT `customer_id` from `customers_merchant_map` where `is_active` <> 0 and `merchant_id` = '.$id);
                       // echo 
                        //$stmt->bind_param('i', $id);
                        $stmt->bind_result($uid);
                        $stmt->execute();

                        $uids = array();
                        while($stmt->fetch()) {
                            $uids[] = $uid;
                        }
                        $stmt->close();

                        $uids = array_intersect($anniversary_uids, $uids);

                        if(!isset($is)) $is = $uids;
                        else if($type == 'any') $is = array_unique(array_merge($is, $uids));
                        else $is = array_intersect($is, $uids);

                         echo "anniversary UID size " . sizeof($uids)."\n";
                         echo "anniversary IS size " . sizeof($is)."\n";
                    }
                    if($run == $count && !$first) $query .= ')';
                }//foreach
            }
        }

        $uids = array();
        if(!isset($filter->parameters) || count($filter->parameters) == 0 || !$first) {
            
            $stmt = $DB->prepare($query);

            echo "\nfinal Query \n";
            echo $query."\n";

            $stmt->bind_result($uid);
            $stmt->execute();

            while($stmt->fetch()) {
                $uids[] = $uid;
            }
            $stmt->close();
            echo "uids size out After final query :".(sizeof($uids))."\n";
            echo "is size out After final query:".(sizeof($is))."\n\n";
            if(isset($is)) {
            	echo "1 condition \n";
                if($type == 'any') $uids = array_unique(array_merge($is, $uids));
                else $uids = array_intersect($is, $uids);

                echo "Final uids size  :".(sizeof($uids))."\n";
         	echo "Final is size :".(sizeof($is))."\n";
            }

            

        }
        else if(isset($is)){ 
        	print_r($is);
        	$uids = $is; 
        	echo "2 condition \n";
        }
       
        echo "Final uids size  :".(sizeof($uids))."\n";
         echo "Final is size :".(sizeof($is))."\n";

        return $uids;
    }

?>