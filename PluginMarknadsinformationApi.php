<?php
class PluginMarknadsinformationApi{
  private $settings = null;
  private $url = 'https://urval.personkontakt.se/api/H%C3%A4mtaPerson?Personnr=';
  public $username = null;
  public $password = null;
  function __construct() {
    wfPlugin::includeonce('wf/yml');
    $temp = wfPlugin::getPluginSettings('marknadsinformation/api', true);
    $this->settings = new PluginWfYml(wfGlobals::getAppDir(). $temp->get('settings') );
    $this->username = $this->settings->get('username');
    $this->password = $this->settings->get('password');
  }
  public function get($pid){
    $url = $this->url.$pid;
    /**
     * get data
     */
    wfPlugin::includeonce('server/json');
    $server = new PluginServerJson();
    $server->username = $this->username;
    $server->password = $this->password;
    $result = $server->get($url);
    $result = new PluginWfArray($result);
    /**
     * 
     */
    $result->set('status', 'ok');
    if(is_null($result->get('Personnr'))){
      $result->set('status', 'not found');
    }
    /**
     * db
    Personnr: '_'
    Tilltalsnamn: _
    Förnamn: '_'
    Mellannamn: null
    Efternamn: '_'
    Kommun: '1380'
    COadress: null
    Gatuadress: '_'
    Postnr: '_'
    Postort: '_'
     * 
     */
    wfPlugin::includeonce('marknadsurval/db');
    $db = new PluginMarknadsurvalDb();
    $insert = array();
    $insert['pid'] = $pid;
    $insert['first_name'] = $result->get('Förnamn');
    $insert['given_name'] = $result->get('Tilltalsnamn');
    $insert['surname'] = $result->get('Efternamn');
    $insert['address'] = $result->get('Gatuadress');
    $insert['zip'] = $result->get('Postnr');
    $insert['city'] = $result->get('Postort');
    $insert['moved_at'] = null;
    $insert['status'] = $result->get('status');
    $insert['api_name'] = 'marknadsinformation';
    $db->marknadsurval_cupdate_insert($insert);
    /**
     * log
     */
    $log = new PluginWfYml(wfGlobals::getAppDir().'/../buto_data/theme/[theme]/plugin/marknadsinformation/api/'.$this->username.'/'.date('Y-m').'.yml');
    $log->set('log/', array('time' => date('Y-m-d H:i:s'), 'pid' => wfUser::getSession()->get('plugin/banksignering/ui/pid'), 'result' => $result->get()));
    $log->set('count', sizeof($log->get('log')));
    $log->save();
    /**
     * 
     */
     return $result;
  }
}
