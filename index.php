<?php
/*
    Name           :  Execute exterme Teminal Command process and generate API response
    -----------------------------------------------------------------------------------------------
    Author         :  Danish Absar
    -----------------------------------------------------------------------------------------------
    Purpose        :  Establish a bridge between Web and system core shell through php
    -----------------------------------------------------------------------------------------------
*/


ini_set('max_execution_time', -1);

    class Execute_Script {
        /* Member variables */
        var $hostname;
        var $username;
        var $password;
        var $command;
        var $script_output = "";
        var $time_interval = "N/A";
        var $brand_id;
        
        function __construct( $host, $user, $pass )
        {
            $this->hostname = $host;
            $this->username = $user;
            $this->password = $pass;
            $this->script_output = "Script Not Executed";
        }
        
        /* Member functions */
        function fire_Script()
        {
            $start_time_stamp = new DateTime(date("Y-m-d H:i:s"));
            ////Prepare Command that need to be executated 
            //$this->setCommand();
            //Initializing Machine Connection
            $connection = ssh2_connect($this->hostname, 22);
            if (ssh2_auth_password($connection, $this->username, $this->password))
            {   //Execute Command
                $stream = ssh2_exec($connection, $this->command);
                stream_set_blocking($stream, true);
                $stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
                $this->script_output = stream_get_contents($stream_out);
                $stream = ssh2_exec($connection, "pkill -9 -f tab_checkout.py");
                //Terminating Machine Connection
                ssh2_exec($connection, 'exit');
                unset($connection);
                $this->status = 200;
                $this->message = "Script executed Successfully";
            }
            else
            {
                $this->status = 404;
                $this->message = "Unable to Connect to Machine";
            }
            $stop_time_stamp = new DateTime(date("Y-m-d H:i:s"));
            $interval = date_diff($start_time_stamp, $stop_time_stamp);
            $this->time_interval = $interval->format('%h:%i:%s');
            //echo $time_interval->format('%h:%i:%s');
        }
        
        function setCommand()
        {
            $this->command = '/usr/bin/python /var/www/html/tab_account/tab_checkout.py '.$this->brand_id;
        }
        
        function set_kill_Command()
        {
            $this->command = 'pkill -9 -f tab_checkout.py';
        }
        
        function set_Json_Response()
        {
            //Prepare Command that need to be executated 
            $this->setCommand();
            //Fire Script 
            $this->fire_Script();
            $response = array("status"=>$this->status,
                              "script_ouput"=>$this->script_output,
                              "executation_time"=>$this->time_interval,
                              "message"=>$this->message);
            
            echo json_encode($response, JSON_PRETTY_PRINT);
        }
        
        function kill_Process()
        {
            //Prepare Command that need to be executated 
            $this->set_kill_Command();
            //Fire Script 
            $this->fire_Script();
            $response = array("status"=>$this->status,
                              "script_ouput"=>$this->script_output,
                              "executation_time"=>$this->time_interval,
                              "message"=>$this->message);
            
            echo json_encode($response, JSON_PRETTY_PRINT);
        }
        
    }
    
    class Check_script_status
    {
        function __construct()
        {            
            $process_info = array();
            $output = shell_exec('ps -aux | grep "tab_checkout.py"');
            $output = array_filter(explode("\n",$output));
            foreach ($output as $key => $val)
            {
                if (strpos($val, 'www-data ') !== false)
                {
                    unset($output[$key]);
                }
                else
                {
                    $val = preg_replace('/\s+/', ' ', $val);
                    array_push($process_info, array_filter(explode(" ",$val)));   
                }
                
            }
            if(empty($output))
            {
                $status         = "Not Running";
                $status_code    = 200;
            }
            else
            {
                $status         = "Running";
                $status_code    = 404;
            }
            
            $response = array("status"=>$status_code,
                            "script_ouput"=>$process_info,
                            "script_status"=>$status);
            echo json_encode($response, JSON_PRETTY_PRINT);
        }
    }
    
//Create Class Object and Call method
    
$call_Execute_Script = new Execute_Script("HOST", "USER", "PASS");
if(isset($_GET['brand_id']))
{
    $call_Execute_Script->brand_id = htmlspecialchars($_REQUEST['brand_id']);
    $call_Execute_Script->set_Json_Response();    
}
if(isset($_GET['status']))
{
    $call_Check_script_status = new Check_script_status();
}
if(isset($_GET['stop']))
{
    $call_Execute_Script->kill_Process();    
}

?>
