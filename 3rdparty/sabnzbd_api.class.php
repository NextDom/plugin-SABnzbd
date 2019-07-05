<?php
class sabnzbd_api {

	##protected $url_api_im = 'https://iam-api.dss.sabnzbdgroup.net/api/v3/';
	##protected $url_api_track = 'https://amc-api.dss.sabnzbdgroup.net/v1/';
	protected $username;
	protected $password;
	protected $token;
	protected $provider;

    function login($host, $port, $API,$mode)
	{
            log::add('sabnzbd','info','login..');
            $this->username = $username;
            $this->password = $password;
	    $url = $host.":".$port."/api?output=json&apikey=".$API."&mode=".$mode;
	    $result = $this->get_api($url);
        log::add('sabnzbd','info',$url);
        log::add('sabnzbd','info',$result);
	    return $result;
	}

	private function get_api($url,$page, $fields = null)
	{
		$session = curl_init();

		curl_setopt($session, CURLOPT_URL, $url);
		curl_setopt($session, CURLOPT_HTTPHEADER, "");
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		if ( isset($fields) )
		{
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
		}
		$json = curl_exec($session);
		curl_close($session);
		return $json;
	}

	private function del_api($page)
	{
		$session = curl_init();

		curl_setopt($session, CURLOPT_URL, $this->url_api_im . $page);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers());
		curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$json = curl_exec($session);
		curl_close($session);
		return json_decode($json);
	}


}
?>
