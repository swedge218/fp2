<?php
            $commodity_data_url = 'https://dhis2nigeria.org.ng/dhis/api/analytics.json?dimension=dx:lyVV9bPLlVy&dimension=ou:LEVEL-5;s5DPBsdoE8b&dimension=pe:201505&displayProperty=NAME&outputIdScheme=ID';

            $username = 'FP_Dashboard';
            $password = 'CHAI12345';

            try{
                echo 'new curl method: ' . $commodity_data_url . '<br><br>';
                
                echo 'user: ' . $username . '<br/>';
                echo 'pass: ' . $password . '<br/>';
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $commodity_data_url);
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
                $output = curl_exec($ch);
                
                $info = @curl_getinfo($ch);
                echo $response;
                print_r($info);
                
                echo 'output: ' . $output;
                print_r($output); 
                curl_close($ch);
            } catch (Exception $ex) {
                echo $ex->getMessage(); exit;
            }
?>