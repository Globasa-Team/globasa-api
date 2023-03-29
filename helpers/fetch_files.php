<?php

/*
Errors:
 - $out is unset or empty
 - url doesn't exist
 - copy error?
*/

function fetch_files($file_list) {
    $result = true;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
    curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'DEFAULT@SECLEVEL=1');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: 0')); 
    

    foreach($file_list as $id=>$data) {

        echo ("Download: {$id}\n");
        $ofp = fopen($data['filenames'][0], "w");
        curl_setopt($ch, CURLOPT_URL, $data['url']);
        curl_setopt($ch, CURLOPT_FILE, $ofp); 
        $r = curl_exec($ch);
        fclose($ofp);
    
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if ($r && $http_code == 200) {
            echo("\tDownloaded {$id}: {$data['filenames'][0]}\t{$http_code}\n");
            for ($i=1; $i<count($data['filenames']); $i++) {
                echo("\t".$data['filenames'][$i]."\n");
                $r = @copy($data['filenames'][0], $data['filenames'][$i]);
                if($r) {
                    echo("\tBackup copied\n");
                } else {
                    echo("\tBackup failed to copy.\n");
                }
            }
        }
        else {
            echo("\tfailed !!! {$id}\t{$http_code}\tError: ".curl_error($ch)."\n");
            $result = false;
            throw new Exception("curl failed.");
        }

        
    }
    
    
    curl_close($ch);
    return $result;
}