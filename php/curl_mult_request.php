<?php
// debugsober.com/
class MultipleRequester {
     
    private $multiHandle;
    private $singleHandles = array();
    private $completedHandles = array();
     
    public function addGetRequest($id, $url, $curlOptions = array()) {
     
         
        $ch = curl_init();
 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); // 16 second max connect 
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 30 second max operation time // Changed 60 to 120, May 9, 2016
 
        curl_setopt($ch, CURLOPT_POST, 0);
       
        // extra options?
        if (!empty($curlOptions)) {
            curl_setopt_array($ch, $curlOptions);
        }
         
        // Add handle to our array of single handles
        $this->singleHandles[$id] = &$ch;
     
    }
     
    public function addPostRequest($id, $url, $postData, $curlOptions = array()) {
     
        $ch = curl_init();
 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 
        // Turn post fields into a string
        $postString = "";
        $count = 1;
        $length = count($postData);
        foreach($postData as $key => $val) {
             
            $postString .= rawurlencode($key) . '=' . rawurlencode($val);
            if($count != $length) {
                $postString .= "&";
            }
             
            $count++;
        }
         
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
         
        // More options?     
        if (!empty($curlOptions)) {
            curl_setopt_array($ch, $curlOptions);
        }
         
        $this->singleHandles[$id] = &$ch;
                 
    }
     
    public function execute($howMany = -1) {
     
        $this->multiHandle = curl_multi_init();
         
        $currentHandles = array();
         
        $count = 0;
        foreach($this->singleHandles as $id => $ch) {
             
            if($howMany != -1 && $howMany <= $count) {
                // They don't want to request any more than this right now...
                break;
            }
             
            $currentHandles[$id] = $this->singleHandles[$id];
             
            unset($this->singleHandles[$id]);
             
            curl_multi_add_handle($this->multiHandle, $currentHandles[$id]);
 
             
            $count++;
        }
     
        $running = null;
         
        do {
         
            curl_multi_exec($this->multiHandle,$running);
             
        } while ($running > 0);
         
        // Remove the individual handles
        foreach($currentHandles as $id => $ch) {
         
			
			curl_multi_remove_handle($this->multiHandle, $ch);
			$this->completedHandles[$id] = $ch;


        }
         
        curl_multi_close($this->multiHandle);
        $this->multiHandle = null;
        
    }
     
    public function getCompletedIds() {
         
        $ids = array();
         
        foreach($this->completedHandles as $id => $handle) {
             
            array_push($ids, $id);
        }
         
        return $ids;
     
    }
     
    public function deleteCompletedHandle($id) {
     
        @curl_close($this->completedHandles[$id]);
        unset($this->completedHandles[$id]);
     
     
    }
     
    public function moreRemaining() {
     
        if(count($this->singleHandles) > 0) {
            return true;
        }
        else {
            return false;
        }
     
    }
     
    public function getContent($id) {
     
        return curl_multi_getcontent($this->completedHandles[$id]);
     
    }
     
    public function getStatusCode($id) {
        return curl_getinfo($this->completedHandles[$id], CURLINFO_HTTP_CODE);
    }
     
    public function getContentType($id) {
        return curl_getinfo($this->completedHandles[$id], CURLINFO_CONTENT_TYPE );
    }
    
    public function getEffectiveURL($id) {
        return curl_getinfo($this->completedHandles[$id], CURLINFO_EFFECTIVE_URL );
    }
     
    public function finish() {
     
        // Close single handles...
     
        foreach($this->singleHandles as $id => $ch) {
         
            @curl_close($ch);
            $this->singleHandles[$id] = null;
         
        }
         
        // Close completed handles
         
        foreach($this->completedHandles as $id => $ch) {
         
            @curl_close($ch);
            $this->completedHandles[$id] = null;
             
        }
    }
     
    public function __destruct() {
     
        if(count($this->singleHandles) > 0) {
            $this->finish();
        }
         
    }
     
 
}
?>
