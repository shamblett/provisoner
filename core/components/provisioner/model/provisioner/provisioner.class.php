<?php
/**
 * Main Provisioner class
 *
 * @category  Provisioning
 * @author    S. Hamblett <steve.hamblett@linux.com>
 * @copyright 2009 S. Hamblett
 * @license   GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link      none
 *
 * @package provisioner
 */

/**
 * Main Provisioner class
 *
 *
 * @category   Provisioning
 * @author     S. Hamblett <shamblett@cwazy.co.uk>
 * @copyright  2009 S. Hamblett
 * @license    GPLv3 http://www.gnu.org/licenses/gpl.html
 * @link       none
 * @see        none
 * @deprecated no
 *
 * @package provisioner
 */

class Provisioner {

    /* Constants */
    const LOGGEDIN = 1;
    const LOGGEDOUT = 2;
    const NORESOURCE = -1;
    const NOUSER = -1;
    const CATEGORYALREADYEXISTS = -1;
    const NULLCATEGORY = -2;
    const GATEWAY_INSTALLED = 53;
    const AUTHHEADER = 'modAuth: ';

    /**
     * @var config local configuration settings
     * @access public
     */
    var $config = array();

    /**
     * @var cookiefile CURL cookie file
     * @access private
     */
    private $_cookiefile;

    /**
     * @var connectorURL remote site connector URL
     * @access private
     */
    private $_connectorURL;

    /**
     * @var curlSession CURL session object
     * @access private
     */
    private $_curlSession;

    /**
     * @var loggedin logged in indicator
     * @access private
     */
    private $_loggedin;

    /**
     * @var siteid remote site identifier
     * @access private
     */
    private $_siteId;

    /**
     * @var siteid remote site identifier header string
     * @access private
     */
    private $_siteIdString;

    /**
     * @var account logged in account
     * @access private
     */
    private $_account;

    /**
     * @var category the category of the provisioner locally
     * @access private
     */
    private $_category;

    /**
     * @var usergroup the user group of the provisioner locally
     * @access private
     */
    private $_usergroup;

    /**
     * @var importpath the path to import files too
     * @access private
     */
    private $_importpath;

    /**
     * @var remoteIsEvo indicator, true if evolution
     * @access private
     */
    private $_remoteIsEvo;

    /**#@+
     * Constructor
     *
     * @param object &$modx class we are using.
     *
     * @return Provisioner A unique Provisioner instance.
     */
    function Provisioner(&$modx) {
        $this->modx =& $modx;
    }

    /**
     * Initalize the class
     *
     * @access public
     * @param string $ctx context we are using.
     *
     * @return void
     */
    function initialize($ctx = 'mgr') {

        /* MODx provides us with the 'namespace_path' config setting
        * when loading custom manager pages. Set our base and core paths */
        $this->config['base_path'] = $this->modx->getOption('provisioner.core_path',null,$this->modx->getOption('core_path').'components/provisioner/');
        //$this->modx->config['namespace_path'];
        $this->config['core_path'] = $this->config['base_path'];

        /* add the Provisioner model into MODx */
        $this->modx->addPackage('provisioner', $this->config['core_path'].'model/');

        /* Load the 'default' lang foci, which is default.inc.php. */
        $this->modx->lexicon->load('provisioner:default');

        /* Load core user lexicon */
        $this->modx->lexicon->load('core:user');

        switch ($ctx) {
            case 'mgr': /* we only want this stuff to really happen in mgr context anyway */
                $this->config['template_path'] = $this->config['core_path'].'templates/';
                $this->modx->smarty->setTemplatePath($this->config['template_path']);

                /* Refresh the smarty config and lexicon so it loads newly loaded custom data */
                $this->modx->smarty->assign('_config', $this->modx->config);
                $this->modx->smarty->assign('_lang', $this->modx->lexicon->fetch());
                break;
        }

        /* Load our persistant class parameters from system settings */
        $this->_loadSessionParams();

        /* Get our category */
        $category = $this->modx->getObject('modCategory', array('category' => 'provisioner'));
        if ($category == false ) {

            $this->_category = 0;

        } else {

            $this->_category = $category->get('id');

        }

        /* Get our user group */
        $ug = $this->modx->getObject('modUserGroup', array('name' => 'Provisioner'));
        if ($ug == null ) {

            $this->_usergroup = 0;

        } else {

            $this->_usergroup = $ug->get('id');
        }

        /* Get our user path for file imports */
        $this->_importpath = $this->modx->getOption('assets_path').'components/provisioner/imports/';

        /* Create the remote header site identity string for Revolution installations */
        $this->_siteIdString = Provisioner::AUTHHEADER . $this->_siteId;
    }

    /**
     * Login function
     *
     * @access public
     * @param $account the name of the account to log in to.
     * @param $password  the password to use.
     * @param $url the remote site connector URL to use.
     * @param $remoteSiteTypeIsEvo if true, the remote site is Evolution
     * @param $siteId remote site identifier for Revolution
     * @param $error the remote site response string on failure.
     *
     * @return boolean
     */
    function login($account, $password, $url, $remoteSiteTypeIsEvo, $siteId, &$error) {

        $status = false;
        $error = 'none';

        /* Check for CURL, if not present no point going any further */
        if ( !function_exists('curl_init') ) {
		
			 $error = $this->modx->lexicon('nocurl');
             return $status;
		}
		
        $this->_connectorURL = $url;

        /* CURL initialisation */

        /* Create a temp file for session cookies */
        $this->_cookiefile = tempnam("", "CURL");

        /* Initialise CURL and set the return transfer and cookie options */
        $this->_curlSession = curl_init();
        curl_setopt($this->_curlSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_curlSession, CURLOPT_COOKIEFILE, $this->_cookiefile);
        curl_setopt($this->_curlSession, CURLOPT_COOKIEJAR, $this->_cookiefile);
       
        /* If the remote site is evolution check for the gateway code first */
        if ( $remoteSiteTypeIsEvo ) {

            if ( !$this->_evolutionGatewayExists() ) {

                    $error = $this->modx->lexicon('norevogateway');
                    return $status;
            }

         }

        /* Login, encoding the parameters as a POST request to the remote site */
        if ( $remoteSiteTypeIsEvo ) {
            curl_setopt($this->_curlSession, CURLOPT_URL, $this->_connectorURL.'/manager/processors/login.processor.php');
        } else {
            $this->_siteIdString = Provisioner::AUTHHEADER . $siteId;
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            curl_setopt($this->_curlSession, CURLOPT_URL, $this->_connectorURL.'/security/login.php');
        }
        $codedPassword = urlencode($password);
        curl_setopt($this->_curlSession, CURLOPT_POST, true);
        if ( $remoteSiteTypeIsEvo ) {
            $loginstring = 'username='.$account.'&password='.$codedPassword.'&ajax=1';
        } else {
            $loginstring = 'username='.$account.'&password='.$codedPassword.'&login_context=mgr';
        }
        curl_setopt($this->_curlSession, CURLOPT_POSTFIELDS, $loginstring);


        $result = curl_exec($this->_curlSession);

        /* Check the response body for success/fail */
        if ( $remoteSiteTypeIsEvo ) {

            if ( strstr($result, 'Location') === FALSE ) {

                $error = $result;

            } else {

                $status = true;
                $this->_setSessionParams($this->_cookiefile,
                        $this->_connectorURL, $account, true, $siteId );
            }

        } else {

            /* Try and decode it */
            $resultarray = $this->modx->fromJSON($result);
            if ( is_object($resultarray) ) {

                /* Check for success */
                if ( $resultarray->success == 1 ) {

                    /* Worked, log ourselves in */
                    $status = true;
                    $this->_setSessionParams($this->_cookiefile,
                            $this->_connectorURL, $account, false, $siteId );

                } else {

                    /* Get the error from the message */
                    $error = $resultarray->message;
                }

           } elseif ( is_array($resultarray) ) {

                /* Check for success */
                if ( $resultarray['success'] == 1 ) {

                    /* Worked, log ourselves in */
                    $status = true;
                    $this->_setSessionParams($this->_cookiefile,
                            $this->_connectorURL, $account, false, $siteId );

                } else {

                    /* Get the error from the message */
                    $error = $resultarray['message'];
                }

           } else {

               $error = $this->_decodeRemoteResponse($result, $status);

           }

        }

        /* Return the status */
        return $status;

    }

    /**
     * Logout function
     *
     * @access public
     * @param $error any returned error string.
     * @param $remoteSiteTypeIsEvo if true this is an Evolution site
     * @param $account the evolution account to log out
     * @return boolean
     */
    function logout(&$error,$remoteSiteTypeIsEvo, $account) {

        $status = false;

        /* Logged in check */
        if ( !$this->_loggedin ) {

            $error = $this->modx->lexicon('notloggedin');
            return false;
        }

        /* Log ourselves out */
        if ( $remoteSiteTypeIsEvo ) {
            curl_setopt($this->_curlSession, CURLOPT_URL, $this->_connectorURL.'/manager/index.php?a=8');
            curl_setopt ($this->_curlSession, CURLOPT_REFERER, $this->_connectorURL . '/');
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            curl_setopt($this->_curlSession, CURLOPT_URL, $this->_connectorURL.'/security/logout.php?login_context=mgr');

        }

        $result = curl_exec($this->_curlSession);

        /* Check the response body for success/fail */
        if ( $remoteSiteTypeIsEvo ) {

            if ( $result != '' ) {

                $status = false;
                $error = $result;

            } else {

                $status = true;
            }

        } else {

            /* Try and decode it */
            $resultarray = $this->modx->fromJSON($result);
            if ( is_array($resultarray) ) {

                /* Check for success */
                if ( $resultarray['success'] == 1 ) {

                    $status = true;

                } else {

                    /* Get the error from the message */
                    $error = $resultarray['message'];
                }

            } else {

                /* Not JSON encoded, parse for HTTP errors */
                $error = $this->_decodeRemoteResponse($result, $status);
            }
        }

        /* Log ourselves out */
        $logoutsetting = $this->modx->getObject('modSystemSetting',
                array ('key' => 'status',
                'namespace' => 'provisioner'));
        $logoutsetting->set('value', Provisioner::LOGGEDOUT);
        $logoutsetting->save();

        /* Remove the cookie file */
        unlink($this->_cookiefile);

        return $status;

    }

    /**
     * Get Resources function
     *
     * @access public
     * @param $id the id of the resource in 'context_id' form.
     * @param $type the type of the resource.
     * @param $errorstring a returned error string.
     * @param $nodes the resource nodes.
     * @return boolean
     */

    function getResources($id, $type, &$errorstring, &$nodes) {


        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }

        /* Get the resources, ask for them with string literals for revolution */
        if ( $this->_remoteIsEvo ) {
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getnodes&entity=resources&id=$id&type=$type&stringLiterals=1";
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $url = $this->_connectorURL."/resource/index.php?action=getnodes&id=$id&type=$type&stringLiterals=1";
        }
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Need to adjust the menus here to remove the pre-supplied ones and add ours */

        /* Decode the JSON string */
        $resultarray = $this->modx->fromJSON($result);

        /* Create our menu's */
        $menu[] = array(
                'id' => 'pv-import-resource',
                'text' => $this->modx->lexicon('import_resource'),
                'handler' => 'function(itm,e) {
                        this.importResource(itm,e);
                    }'
        );

        /* Import and convert for Evolution */
        if ( $this->_remoteIsEvo ) {
            $menu[] = array(
                    'id' => 'pv-import-convert-resource',
                    'text' => $this->modx->lexicon('import_convert_resource'),
                    'handler' => 'function(itm,e) {
                        this.importConvertResource(itm,e);
                    }'
            );
        }

        /* And add it, clearing other fields where needed */
        foreach ( $resultarray as &$jsonresult) {

            $jsonresult['href'] = "";
            /* Dont add on context nodes */
            if ( $jsonresult['type'] == 'modResource' ) {

                $jsonresult['menu'] = array ('items' => $menu);
            } else {

                $jsonresult['menu'] = array ('items' => null);

            }

        }

        /* Re-encode using the responder class toJSON so we encode the js properly */
        $nodes = $this->modx->response->toJSON($resultarray);
        return true;

    }

    /**
     * Import Resources function
     *
     * @access public
     * @param $id the id of the resource in 'context_id' form.
     * @param $folder if the resource is a folder
     * @param $convert if we should run the resource content through tag conversion
     * @param $errorstring a returned error string.
     * @return boolean
     */

    function importResources($id, $folder, $convert, &$errorstring) {

        $result = false;

        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }

        /*  Resource id check */
        if ( $id == Provisioner::NORESOURCE ) {

            $errorstring = $this->modx->lexicon('invalidresourceid');
            return false;
        }

        /* Check for single resource or tree */
        if ( $folder == true) {

            /* Tree, id contains the context */
            $result = $this->_getTreeResources($id, $errorstring);

        } else {

            /* Single resource, just pass its id */
            $parts= explode('_', $id);
            $id = $parts[1];
            $result = $this->_getSingleResource($id, $convert, $errorstring);
        }

        return $result;
    }

    /**
     * Status function
     *
     * @access public
     * @return the current provisioner status
     */

    function status() {

        $response = array();
        
        $response['loggedin'] = false;
        
        /* Logged in check */
        if ( $this->_loggedin ) {

            $response['url'] = $this->_connectorURL;
            $response['site'] = $this->_remoteIsEvo;
            $response['account'] = $this->_account;
            $response['siteid'] = $this->_siteId;
            $response['loggedin'] = true;

        }

        return $response;

    }

    /**
     * Get Files function
     *
     * @access public
     * @param $node the node of the file tree.
     * @param $errorstring a returned error string.
     * @param $nodes the element nodes.
     * @return boolean
     */

    function getFiles($node, &$errorstring, &$nodes) {


        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }

        /* Get the files, ask for them with string literals */
        if ( $this->_remoteIsEvo ) {
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getnodes&entity=files&id=$node";
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $url = $this->_connectorURL."/browser/index.php?action=directory/getList&id=$node&stringLiterals=1";
        }
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Need to adjust the menus here to remove the pre-supplied ones and add ours */

        /* Decode the JSON string */
        $resultarray = $this->modx->fromJSON($result);

        /* Create our menu */
        $menu[] = array(
                'id' => 'pv-import-file',
                'text' => $this->modx->lexicon('import_file'),
                'handler' => 'function(itm,e) {
                        this.importFile(itm,e);
                    }'
        );

        /* And add it, clearing other fields where needed */
        foreach ( $resultarray as &$jsonresult) {

            $jsonresult['menu'] = array('items' => $menu);

        }

        /* Re-encode using the responder class toJSON so we encode the js properly */
        $nodes = $this->modx->response->toJSON($resultarray);
        return true;

    }


    /**
     * Get Users function
     *
     * @access public
     * @param $start the start record, usually 0
     * @param $limit the amount to get
     * @param $username an optional user name filter
     * @param $errorstring a returned error string.
     * @param $nodes the user rows.
     * @return boolean
     */

    function getUsers($start, $limit, $username, &$errorstring, &$nodes) {

        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }

        /* Get the users */
        if ( $this->_remoteIsEvo ) {
            if ( $username == 'none' ) {
                $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getusers&entity=users&start=$start&limit=$limit";
            } else {
                $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getusers&entity=users&username=$username";

            }
        } else {
			curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            if ( $username == 'none' ) {
                $url = $this->_connectorURL."/security/user.php?action=getlist&start=$start&limit=$limit";

            } else {
                $url = $this->_connectorURL."/security/user.php?action=getlist&username=$username";

            }
        }

        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Decode the result string into a JSON array */
        $resultarray = $this->_fromList($result);

        /* Create our menu */
        $menu[] = array(
                'text' => $this->modx->lexicon('import_user'),
                'handler' => 'this.importUser'
        );

        /* And add it, clearing other fields where needed */
        foreach ( $resultarray as &$jsonresult) {

            $jsonresult['menu'] = $menu;

        }

        /* Re-encode using the responder class outputarray */
        $count = count($resultarray);
        $nodes = $this->modx->response->outputArray($resultarray, $count);

        return true;


    }


    /**
     * Get Packages function
     *
     * @access public
     * @param $start the start record, usually 0
     * @param $limit the amount to get
     * @param $errorstring a returned error string.
     * @param $nodes the package rows.
     * @return boolean
     */

    function getPackages($start, $limit, &$errorstring, &$nodes) {

        $localsignatures = array();
        $localpackages = array();

        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }

        /* Get the packages */
        if ( $this->_remoteIsEvo ) {

            /* Dummy package just to indicate we are an evolution site */
            $package = array();
            $installedresultarray = array();
            $package['signature'] = 'Remote site is Evolution - no packages';
            $installedresultarray[] = $package;

            $count = count($installedresultarray);
            $nodes = $this->modx->response->outputArray($installedresultarray, $count);
            /* Can't fail */
            return true;

        }

        $url = $this->_connectorURL."/workspace/packages.php?action=getlist&start=$start&limit=$limit";
        curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Decode the result string into a JSON array */
        $resultarray = $this->_fromList($result);

        /* Get the locally installed package signatures */
        $c = $this->modx->newQuery('transport.modTransportPackage');
        $c->where(array(
                'workspace' => 1,
        ));
        $localpackages = $this->modx->getCollection('transport.modTransportPackage', $c);
        $howmany = count($localpackages);
        foreach ($localpackages as $key => $localpackage ) {

            $locallyinstalled = $localpackage->get('installed');
            if ( $locallyinstalled != null ) {
                $localsignatures[] = $key;
            }
        }

        /* Remove the menus and find out if installed locally or not
           only if the package is installed remotely */
        foreach ( $resultarray as $key => &$package ) {

            $package['menu'] = null;
            if ( $package['installed'] != null ) {
                $signature = $package['signature'];
                $package['localinstall'] = in_array($signature, $localsignatures);
                $installedresultarray[] = $package;
            }

        }


        /* Re-encode using the responder class outputarray */
        $count = count($installedresultarray);
        $nodes = $this->modx->response->outputArray($installedresultarray, $count);

        return true;
    }

    /**
     * Get Elements function
     *
     * @access public
     * @param $id the id of the element in 'context_id' form.
     * @param $type the type of the element.
     * @param $errorstring a returned error string.
     * @param $nodes the element nodes.
     * @return boolean
     */

    function getElements($id, $type, &$errorstring, &$nodes) {


        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }

        /* Get the elements, ask for them with string literals */
        if ( $this->_remoteIsEvo ) {
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getnodes&entity=elements&id=$id&type=$type&stringLiterals=1";
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $url = $this->_connectorURL."/element/index.php?action=getnodes&id=$id&type=$type&stringLiterals=1";
        }
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Need to adjust the menus here to remove the pre-supplied ones and add ours */

        /* Decode the JSON string */
        $resultarray = $this->modx->fromJSON($result);

        /* Create our menu */
        $g = explode('_',$id);
        if ( $g[0] != 'root') {

            $menu[] = array(
                    'id' => 'pv-import-element',
                    'text' => $this->modx->lexicon('import_element'),
                    'handler' => 'function(itm,e) {
                            this.importElement(itm,e);
                        }'
            );
        }

        /* Import and convert for Evolution templates and chunks */
        if ( $this->_remoteIsEvo ) {
            if ( ($type == 'template') || ($type == 'chunk')) {

                $menu[] = array(
                        'id' => 'pv-import-convert-element',
                        'text' => $this->modx->lexicon('import_convert_resource'),
                        'handler' => 'function(itm,e) {
                        this.importConvertElement(itm,e);
                        }'
                );
            }
        }

        /* And add it, clearing other fields where needed */
        foreach ( $resultarray as &$jsonresult) {

            $jsonresult['href'] = "";
            $jsonresult['menu'] = array ('items' => $menu);

        }

        /* Re-encode using the responder class toJSON so we encode the js properly */
        $nodes = $this->modx->response->toJSON($resultarray);

        return true;

    }

    /**
     * Import Files function
     *
     * @access public
     * @param $file the path of the file relative to the root installation.
     * @param $folder if the node is a folder
     * @param $realpath the real path of the file
     * @param $prependpath the path to the firl in the installation
     * @param $errorstring a returned error string.
     * @return boolean
     */

    function importFiles($file, $folder, $realpath, $prependpath, &$errorstring) {

        $result = false;

        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }

        /* Parameters check */
        if ( ($file == 'none') || ($realpath == 'none')) {

            $errorstring = $this->modx->lexicon('nofilename');
            return false;

        }

        if ( $realpath == 'none') {

            $errorstring = $this->modx->lexicon('nopathname');
            return false;

        }

        /* Get the files requested */
        if ( $folder == false ) {

            /* Not a folder */
            $result = $this->_getSingleFile($file, $errorstring);

        } else {

            $result = $this->_getFileFolder($file, $errorstring);
        }

        return $result;

    }

    /**
     * Get File Folder function
     *
     * @access private
     * @param $file the path of the folder relative to the root installation.
     * @param $errorstring a returned error string.
     * @return boolean
     */
    function _getFileFolder($file, &$errorstring) {

        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }

        /* Get the directory name */
        $filenamearray = explode('/', $file);
        $dir = end($filenamearray);

        /* Make the directory in the imports folder */
        $dirname = $this->_importpath.$dir;
        if ( mkdir($dirname) == false ) {

            $errorstring = $this->modx->lexicon('failedtocreatelocalfolder');
            return false;
        }

        /* Get a list of files in this directory */
        if ( $this->_remoteIsEvo ) {
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getfiles&entity=files&dir=$file";
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $url = $this->_connectorURL."/browser/directory.php?action=getfiles&dir=$file";
        }
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Decode the result string into a JSON array */
        $resultarray = $this->_fromList($result);

        /* Import each file found */
        foreach ($resultarray as $filerecord ) {

            if ($filerecord['leaf'] == true ) {

                /* Single file import */
                $fullname = $filerecord['pathname'];
                if ( $this->_remoteIsEvo ) {
                    $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=files&file=$fullname";
                } else {
                    curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
                    $url = $this->_connectorURL."/browser/file.php?action=get&file=$fullname";
                }
                curl_setopt($this->_curlSession, CURLOPT_URL, $url);

                $fileresult = curl_exec($this->_curlSession);

                /* Decode the JSON string */
                $filearray = $this->modx->fromJSON($fileresult);

                /* Get the contents and create the file */
                $filerecord = $filearray['object'];
                $filecontents = $filerecord['content'];
                $filename = $filerecord['name'];
                $errorstring = $this->_fileCreateCheck($filecontents);
                if ( $errorstring != '' ) return false;
                $createfile = $this->_importpath.$dir.'/'.$filename;
                if (file_put_contents($createfile, $filecontents) == false ) {

                    $errorstring = $this->modx->lexicon('failedtocreatelocalfile');
                    return false;
                }

            }

        }

        return true;

    }


    /**
     * Get Single File function
     *
     * @access private
     * @param $file the path of the file relative to the root installation.
     * @param $errorstring a returned error string.
     * @return boolean
     */
    function _getSingleFile($file, &$errorstring) {

        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }

        /* Get a list of files in this directory, we need to extract the fullpath */
        $dir = dirname($file);
        if ( strlen($dir) == 1 ) $dir = "root";

        if ( $this->_remoteIsEvo ) {
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getfiles&entity=files&dir=$dir";
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $url = $this->_connectorURL."/browser/directory.php?action=getfiles&dir=$dir";
        }
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Decode the result string into a JSON array */
        $resultarray = $this->_fromList($result);

        /* Find the file requested */
        $filenamearray = explode('/', $file);
        $filename = end($filenamearray);
        foreach ($resultarray as $filerecord ) {

            if ( $filename == $filerecord['name'] ) {

                $fullname = $filerecord['pathname'];
            }

        }

        /* Get the file */
        if ( $this->_remoteIsEvo ) {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $fullname = rawurlencode($fullname);
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=files&file=$fullname";
        } else {
            $url = $this->_connectorURL."/browser/file.php?action=get&file=$fullname";
        }
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Decode the JSON string */
        $resultarray = $this->modx->fromJSON($result);

        /* Get the contents and create the file */
        $filerecord = $resultarray['object'];
        $filecontents = $filerecord['content'];
         $errorstring = $this->_fileCreateCheck($filecontents);
        if ( $errorstring != '' ) return false;
        $createfile = $this->_importpath.$filename;
        if (file_put_contents($createfile, $filecontents) == false ) {

            $message = print_r($resultarray['message'], true);
            $errorstring = $this->modx->lexicon('failedtocreatelocalfile') . $message;
            return false;
        }

        return true;

    }

    /**
     * Import Users function
     *
     * @access public
     * @param $id the id of the user
     * @param $type, user type for Evolution
     * @param $errorstring a returned error string.
     * @return boolean
     */

    function importUsers($id, $type, &$errorstring) {

        $result = false;

        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }

        /*  User id check */
        if ( $id == Provisioner::NOUSER ) {

            $errorstring = $this->modx->lexicon('invaliduserid');
            return false;
        }
        /* Get the user data */
        if ( $this->_remoteIsEvo ) {
            $idArray = explode('_', $id);
            $id = $idArray[0];
            $type = 'manager';
            if ( $idArray[1] == 'w' ) $type = 'web';
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=users&id=$id&type=$type";
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $url = $this->_connectorURL."/security/user.php?action=get&id=$id";
        }
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);
        /* Decode the JSON string */
        $resultarray = $this->modx->fromJSON($result);
        $userdata = $resultarray['object'];

        /* Adjust some user fields to make sense in this environment */
        $userdata['role'] = 0;
        $userdata['blocked'] = true;
        $userdata['logincount'] = 0;
        $userdata['lastlogin'] = 0;
        $userdata['thislogin'] = 0;
        $userdata['failedlogincount'] = 0;
        $userdata['sessionid'] = "";

        /* Recode the DOB */
        if ($userdata['dob'] != 0 ) {

            $userdata['dob'] = strtotime($userdata['dob']);

        }

        /* Create the new user in the provisioner user group */
        $user = $this->modx->newObject('modUser');
        $ugm = $this->modx->newObject('modUserGroupMember');
        $ugm->set('user_group', $this->_usergroup);
        $user->addMany($ugm,'UserGroupMembers');
        $user->set('username', $userdata['username']);
        $user->set('password', $userdata['password']);
        $user->set('cachepwd', "");
        if ($user->save() == false) {

            $errorstring = $this->modx->lexicon('failedtocreatelocaluser');
            return false;
        }

        /* Set the new internal key */
        $userdata['internalKey'] = $user->get('id');
        /* Create the profile(attributes) */
        $user->profile = $this->modx->newObject('modUserProfile');
        $user->profile->fromArray($userdata);
        if ($user->profile->save() == false) {

            $errorstring = $this->modx->lexicon('failedtocreatelocaluserprofile');
            return false;
        }

        /* Done */

        return true;

    }

    /**
     * Import Elements function
     *
     * @access public
     * @param $id the id of the resource in 'context_id' form.
     * @param $convert, run the element through the tag convertor
     * @param $errorstring a returned error string.
     * @return boolean
     */

    function importElements($id, $convert, &$errorstring) {

        $result = false;
        $idparts = array();
        $catStatus = array();

        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }


        /* Split the id into its component parts */
        $idparts = explode('_', $id);

        /*
         * Check import type, if the first field is a type then we
         * need to import a whole type eg templates. Categories are
         * encoded differently and need to be specifically checked for.
        */
        if ( $idparts[0] == 'type') {

            $result = $this->_getElementType($id, $convert, $errorstring);

        } else {

            $catStatus[0] = Provisioner::NULLCATEGORY;
            
            /* Check for category */
            if ( $idparts[0] == 'category' ) {

                $result = $this->_getCategory($idparts, $errorstring, $catStatus );

            } else {

                if ( $idparts[1] == 'category' ) {

                    /* Try and create the category locally */
                    $newid = $idparts[2];
                    $newidparts[0] = 'category';
                    $newidparts[1] = $newid;
                    $result = $this->_getCategory($newidparts, $errorstring, $catStatus );

                    /* If we have a valid category use it to link the elements to */
                    if ( $catStatus[0] == Provisioner::CATEGORYALREADYEXISTS ) {

                        /* Not valid, get the local category */
                        $catname = $catStatus[1];
                        $localcat = $this->modx->getObject('modCategory', array('category' => $catname));
                        $catid = $localcat->get('id');

                    } else {

                        $catid = $catStatus[0];

                    }

                    $result = $this->_getElementType($id, $convert, $errorstring, $catid);

                } else {

                    /* Single element import */
                    $result = $this->_getSingleElement($idparts[0], $idparts[2], $convert, $errorstring, Provisioner::NULLCATEGORY);

                }

            }

        }

        return $result;
    }


    /**
     * Get Element Type function
     *
     * @access private
     * @param $id the id of the element type to get.
     * @param $convert run the elements through tag conversion
     * @param $errorstring a returned error string.
     * @param $catid category to link to
     * @return boolean
     */
    function _getElementType($id, $convert, &$errorstring, $catid) {

        $returnresult = false;

        /* Get the element type */
        $elementparts = explode('_', $id);
        if ( $elementparts[1] == 'category' ) {
            $type =  $elementparts[0];
         } else {
            $type = $elementparts[1];
         }

        /* Get the node list for this element type */
        if ( $this->_remoteIsEvo ) {
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getnodes&entity=elements&id=$id&type=$type&stringLiterals=1";
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $url = $this->_connectorURL."/element/index.php?action=getnodes&id=$id&type=$type&stringLiterals=1";
        }
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Decode the JSON string */
        $resultarray = $this->modx->fromJSON($result);

        /*
         * Get the primary key from each returned element, get it from the remote site
         * and create it locally.
        */
        foreach ($resultarray as $jsonresult ) {

            if ( $jsonresult['classKey'] != 'modCategory') {
                $elementid = $jsonresult['pk'];
                $returnresult = $this->_getSingleElement($type, $elementid, $convert, $errorstring, $catid);
            }

        }

        return $returnresult;

    }

    /**
     * Get Single Element function
     *
     * @access private
     * @param $typ the type of the single element to get.
     * @param $id the id of the single element to get.
     * @param $convert run the element through the tag convertor
     * @param $errorstring a returned error string.
     * @param $category the category id to place the element in
     * @return boolean
     */
    function _getSingleElement($type, $id, $convert, &$errorstring, $category) {

        $elementarray = array();
        $elementedata = array();
        $doConvert = false;

        /* Get the element by its type */
        switch ( $type ) {

            case "snippet":

                if ( $this->_remoteIsEvo ) {
                    $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=elements&id=$id&type=$type";
                } else {
                    curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
                    $url = $this->_connectorURL."/element/snippet.php?action=get&id=$id";
                }
                $classname = 'modSnippet';
                break;

            case "chunk":

                if ( $this->_remoteIsEvo ) {
                    if ( $convert ) $doConvert = true;
                    $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=elements&id=$id&type=$type";
                } else {
                    curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
                    $url = $this->_connectorURL."/element/chunk.php?action=get&id=$id";
                }
                $classname = 'modChunk';
                break;

            case "plugin":

                if ( $this->_remoteIsEvo ) {
                    $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=elements&id=$id&type=$type";
                } else {
                    curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
                    $url = $this->_connectorURL."/element/plugin.php?action=get&id=$id";
                }
                $classname = 'modPlugin';
                break;

            case "template":

                if ( $this->_remoteIsEvo ) {
                    if ( $convert ) $doConvert = true;
                    $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=elements&id=$id&type=$type";
                } else {
                    curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
                    $url = $this->_connectorURL."/element/template.php?action=get&id=$id";
                }
                $classname = 'modTemplate';
                break;

            case "tv":

                if ( $this->_remoteIsEvo ) {
                    $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=elements&id=$id&type=$type";
                } else {
                    curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
                    $url = $this->_connectorURL."/element/tv.php?action=get&id=$id";
                }
                $classname = 'modTemplateVar';
                break;


            default:

                $errorstring = 	$this->modx->lexicon('noelementtype'). " - ".$type;
                return;
        }


        /* Get the remote element */
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);
        $result = curl_exec($this->_curlSession);

        /* Decode the result */
        $elementarray = $this->modx->fromJSON($result);

        /* Check for success */
        if ( $elementarray['success'] != 'true' ) {

            $errorstring =  $elementarray['message'];
            return false;
        }

        /* Create the element locally */
        $elementdata = $elementarray['object'];

        /* Set its category to provisioner if not specified */
        if ( $category == Provisioner::NULLCATEGORY ) {

            $elementdata['category'] = $this->_category;

         } else {

             $elementdata['category'] = $category;

         }

        /* Tag convert if requested */
        if ( $doConvert ) {

            $this->modx->loadClass('modParser095', '', false, true);
            $translator= new modParser095($this->modx);
            if ( $type == 'chunk') {
                $translator->translate($elementdata['snippet']);
            }
            if ( $type == 'template') {
                $translator->translate($elementdata['content']);
            }

        }
        /* Check for newlines in TV's default text field and erase them */
        if ( $type == 'tv' ) {
            if ( ctype_space($elementdata['default_text']) ) {
                $elementdata['default_text'] = "";
            }
        }
        $element = $this->modx->newObject($classname);
        $element->fromArray($elementdata);
        if ($element->save() == false) {

            $errorstring = $this->modx->lexicon('failedtocreatelocalelement');
            $errorstring .= $this->modx->lexicon('already_imported');
            return false;
        }

        return true;

    }

    /**
     * Get Category function
     *
     * @access private
     * @param $id the id of the element type to get.
     * @param $errorstring a returned error string.
     * @param $catStatus returned status of the category creation
     * @return boolean
     */
    function _getCategory($id, &$errorstring, &$catStatus) {

        $resultarray = array();

        $result = false;

        /* Determine if this is all categories or just one */
        if ( count($id) == 2 ) {

            /* Single category */
            $catid = $id[1];
            $result = $this->_getSingleCategory($catid, $errorstring, $catStatus);

        } else {

            /* Get all categories, get the node list */
            if ( $this->_remoteIsEvo ) {
                $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=elements&type=category";
            } else {
                curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
                $url = $this->_connectorURL."/element/index.php?action=getnodes&id=category&stringLiterals=1";
            }

            curl_setopt($this->_curlSession, CURLOPT_URL, $url);

            $result = curl_exec($this->_curlSession);

            /* Decode the JSON string */
            $resultarray = $this->modx->fromJSON($result);

            /* Create each category */
            foreach ($resultarray as $jsonresult ) {

                $catid = $jsonresult['pk'];
                $result = $this->_getSingleCategory($catid, $errorstring, $catStatus);

            }

        }

        return $result;

    }


    /**
     * Get Single Category function
     *
     * @access private
     * @param $id the id of the category to get.
     * @param $errorstring a returned error string.
     * @param $catStatus returned status of the category creation
     * @return boolean
     */

    function _getSingleCategory($id, &$errorstring, &$catStatus ) {

        $categoryarray = array();
        $categoryedata = array();
        $status = array();

        /* Get the category data */
        if ( $this->_remoteIsEvo ) {
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=elements&id=$id&type=category";
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $url = $this->_connectorURL."/element/category.php?action=get&id=$id";
        }
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Decode the result */
        $categoryarray = $this->modx->fromJSON($result);

        /* Check for success */
        if ( $categoryarray['success'] != 'true' ) {

            $errorstring =  $categoryarray['message'];
            return false;
        }

        /* Create the category locally */
        $categorydata = $categoryarray['object'];

        /* Set its parent to the provisioner category */
        $categorydata['parent'] = $this->_category;

        $category = $this->modx->newObject('modCategory');
        $category->fromArray($categorydata);
        if ($category->save() == false) {

            /* This is not an error, the category may already exist,
             * assume this for now
             */

            $status[0] = Provisioner::CATEGORYALREADYEXISTS;
            $status[1] = $category->get('category');


        } else {

            /* Success, return the category id*/
            $status[0] = $category->get('id');

        }

        $catStatus = $status;
        return true;

    }

    /**
     * Get Single Resource function
     *
     * @access private
     * @param $id the id of the resource to get.
     * @param $convert if we should convert tags
     * @param $errorstring a returned error string.
     * @return boolean
     */

    function _getSingleResource($id, $convert, &$errorstring ) {

        $resourcearray = array();
        $resourcedata = array();

        /* Get the resource */
        if ( $this->_remoteIsEvo ) {
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=resources&id=$id";
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $url = $this->_connectorURL."/resource/index.php?action=get&id=$id";
        }

        curl_setopt($this->_curlSession, CURLOPT_URL, $url);
        $result = curl_exec($this->_curlSession);

        /* Decode the result */
        /* If from evolution and we are on PHP 5.2.1, manually do the UTF-8 decode */
        if ( $this->_remoteIsEvo ) {
            if (version_compare(PHP_VERSION, '5.2.1') == 0) {
                $result = utf8_decode($result);
            }
        }
        $resourcearray = $this->modx->fromJSON($result);

        /* Check if we succeeded */
        if ( $resourcearray['success'] != 1 ) {

            $errorstring = $this->modx->lexicon('failedtogetremoteresource')." ".$resourcearray['message'];
            return false;
        }

        /* Extract the resource data */
        $resourcedata = $resourcearray['object'];

        /* Unpublish, remove from menus, clear parent, and set context to provisioner */
        $resourcedata['published'] = 0;
        $resourcedata['hidemenu'] = 1;
        $resourcedata['parent'] = 0;
        $resourcedata['context_key'] = 'provisioner';

        /* Check for tag conversion */
        if ( $convert ) {

            $this->modx->loadClass('modParser095', '', false, true);
            $translator= new modParser095($this->modx);
            $translator->translate($resourcedata['content']);
            $translator->translate($resourcedata['pagetitle']);
            $translator->translate($resourcedata['longtitle']);
        }

        /* Create the resource locally */
        $localresource = $this->modx->newObject($resourcedata['class_key']);
        if ( !$localresource ) {

            $errorstring = $this->modx->lexicon('failedtocreatelocalresource');
            return false;

        }
        $localresource->fromArray($resourcedata);
        if ($localresource->save() == false) {

            $errorstring = $this->modx->lexicon('failedtocreatelocalresource');
            return false;
        }

        return true;

    }

    /**
     * Get Tree Resources function
     *
     * @access private
     * @param $id the id of the resource in 'context_id' form.
     * @param $errorstring a returned error string.
     * @return boolean
     */

    function _getTreeResources($id, &$errorstring ) {

        $idmap = array();
        $ids = array();
        $resourcearray = array();
        $resourcedata = array();

        /* Get the nodes associated with this nodes id */
        $ids = $this->_getNodes($id);

        /* Get the resources and create them and the new/old id map */
        foreach ( $ids as $id ) {

            $parts = explode('_', $id);
            $realid = $parts[1];

            /* Get the resource */
            if ( $this->_remoteIsEvo ) {
                $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=get&entity=resources&id=$realid";
            } else {
                curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
                $url = $this->_connectorURL."/resource/index.php?action=get&id=$realid";
            }
            curl_setopt($this->_curlSession, CURLOPT_URL, $url);

            $result = curl_exec($this->_curlSession);

            /* Decode the result */
            $resourcearray = $this->modx->fromJSON($result);

            /* Extract the resource data */
            $resourcedata = $resourcearray['object'];

            /* Unpublish, remove from menus and set context to provisioner */
            $resourcedata['published'] = 0;
            $resourcedata['hidemenu'] = 1;
            $resourcedata['context_key'] = 'provisioner';

            /* Create the resource locally */
            $localresource = $this->modx->newObject($resourcedata['class_key']);
            $localresource->fromArray($resourcedata);
            if ($localresource->save() == false) {

                $errorstring = $this->modx->lexicon('failedtocreatelocalresource');
                return false;
            }

            /* Get the new id and update the id map */
            $newid = $localresource->get('id');
            $idmap[] = array('oldid' => $realid, 'newid' => $newid );


        }

        /*
     * The parents of the newly created resources will be the old parents,
     * find the mapping of these to the new id's in the id map and update the
     * resource.
        */
        foreach ( $idmap as $id ) {

            $resource = $this->modx->getObject('modResource', array('id' => $id['newid']));
            $oldparent = $resource->get('parent');
            $newparent = $this->_findNewParent($oldparent, $idmap);
            $resource->set('parent', $newparent);
            $resource->save();

        }

        return true;

    }

    /**
     * Find new parent function
     *
     * @access private
     * @param $oldparent the old parent of the resource.
     * @param $idmap the idmap array
     * @return integer
     */
    function _findNewParent($oldparent, $idmap) {

        /* If a mapping cant be found, set the parent to 0. */
        foreach ( $idmap as $id ) {

            if ($id['oldid'] == $oldparent ) {

                return $id['newid'];
            }

        }

        return 0;

    }

    /**
     * Find get nodesfunction
     *
     * @access private
     * @param $id the id of the resource in 'context_id' form.
     * @return node array
     */
    function _getNodes($id) {

        $nodearray = array();
        $resultarray = array();

        /* Set the node we are coming from */
        $nodearray[] = $id;

        /* Get the node list */
        if ( $this->_remoteIsEvo ) {
            $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getnodes&entity=resources&id=$id&stringLiterals=1";
        } else {
            curl_setopt($this->_curlSession, CURLOPT_HTTPHEADER, array($this->_siteIdString));
            $url = $this->_connectorURL."/resource/index.php?action=getnodes&id=$id&stringLiterals=1";
        }
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);

        $result = curl_exec($this->_curlSession);

        /* Decode the JSON string */
        $resultarray = $this->modx->fromJSON($result);

        /* Check for single resources or nodes, recurse if parent node found */
        foreach ( $resultarray as $jsonresult) {

            if ( $jsonresult['leaf'] == 0 ) {

                /* Not a leaf, get the nodes */
                $partnodearray = $this->_getNodes($jsonresult['id']);
                $nodearray = array_merge($nodearray, $partnodearray);

            } else {

                $nodearray[] = $jsonresult['id'];

            }

        }

        /* Return the node list */
        return $nodearray;

    }


    /**
     * Decode remote site response function
     *
     * @access private
     * @param $response the remote site response string.
     * @param the status code to return
     * @return string
     */

    function _decodeRemoteResponse($response, &$statusCode) {

        $statusCode = false;

        /*  could do more here, 301's etc. */

        /*  Look for a 404 returned */
        $found = strstr($response, '404');
        if ( $found ) {

            return $this->modx->lexicon('urlnotfound');

        }

        return $this->modx->lexicon('unknownerror');

    }

    /**
     * Load our session parameters on initialisation function
     *
     * @access private
     *
     */

    function _loadSessionParams() {

        /* First check if we are logged in */

        if ($loginsetting = $this->modx->getObject('modSystemSetting',
        array ('key' => 'status',
        'namespace' => 'provisioner'))) {

            $status = $loginsetting->get('value');

            if ( $status == Provisioner::LOGGEDIN) {

                /* Yes we are, load the session parameters */

                /* Cookie file */
                $cookiesetting = $this->modx->getObject('modSystemSetting',
                        array ('key' => 'cookiefile',
                        'namespace' => 'provisioner'));

                $this->_cookiefile = $cookiesetting->get('value');

                /* Connector URL */
                $urlsetting = $this->modx->getObject('modSystemSetting',
                        array ('key' => 'url',
                        'namespace' => 'provisioner'));

                $this->_connectorURL = $urlsetting->get('value');

                /* Site type */
                $this->_remoteisEvo = false;
                $sitesetting = $this->modx->getObject('modSystemSetting',
                        array ('key' => 'sitetype',
                        'namespace' => 'provisioner'));
                if ( $sitesetting->get('value') == 'evolution') {
                    $this->_remoteIsEvo = true;
                }

                /* Account */
                $account = $this->modx->getObject('modSystemSetting',
                        array ('key' => 'account',
                        'namespace' => 'provisioner'));

                $this->_account = $account->get('value');

                /* Site Identifier*/
                $siteId = $this->modx->getObject('modSystemSetting',
                        array ('key' => 'siteid',
                        'namespace' => 'provisioner'));

                $this->_siteId = $siteId->get('value');

                /* Create the CURL object */
                $this->_curlSession = curl_init();
                curl_setopt($this->_curlSession, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($this->_curlSession, CURLOPT_COOKIEFILE, $this->_cookiefile);
                curl_setopt($this->_curlSession, CURLOPT_COOKIEJAR, $this->_cookiefile);

                $this->_loggedin = true;

            }

        }


    }

    /**
     * Set our session parameters on login function
     *
     * @access private
     * @param $cookiefile cookie file name.
     * @param $connector connector URL
     * @param $account logged in account
     * @param $evolution true if we are an Evolution site
     * @param $siteId Remote site identifier for Revolution
     *
     */
    function _setSessionParams($cookiefile, $url, $account, $evolution, $siteId) {

        /* Status */
        $loginsetting = $this->modx->getObject('modSystemSetting',
                array ('key' => 'status',
                'namespace' => 'provisioner'));
        $loginsetting->set('value', Provisioner::LOGGEDIN);
        $loginsetting->save();

        /* Cookie file */
        $cookiesetting = $this->modx->getObject('modSystemSetting',
                array ('key' => 'cookiefile',
                'namespace' => 'provisioner'));

        $cookiesetting->set('value', $cookiefile);
        $cookiesetting->save();

        /* Connector URL */
        $urlsetting = $this->modx->getObject('modSystemSetting',
                array ('key' => 'url',
                'namespace' => 'provisioner'));

        $urlsetting->set('value', $url);
        $urlsetting->save();

        /* Account */
        $accountsetting = $this->modx->getObject('modSystemSetting',
                array ('key' => 'account',
                'namespace' => 'provisioner'));

        $accountsetting->set('value', $account);
        $accountsetting->save();

        /* Site type */
        $sitesetting = $this->modx->getObject('modSystemSetting',
                array ('key' => 'sitetype',
                'namespace' => 'provisioner'));

        if ( $evolution ) {
            $sitesetting->set('value', 'evolution');
        } else {
            $sitesetting->set('value', 'revolution');
        }

        $sitesetting->save();

        /* Site Identifier for Revolution sites */
        if ( !$evolution ) {
            $siteidsetting = $this->modx->getObject('modSystemSetting',
                array ('key' => 'siteid',
                'namespace' => 'provisioner'));

            $siteidsetting->set('value', $siteId);
            $siteidsetting->save();
        }

        $this->_loggedin = true;

    }

    /**
     * Decode list encoded results from the remote site
     *
     * @access private
     * @param $results result string.
     *
     */
    function _fromList($results) {
        $resultarray = array();

        /* Get the JSON Data */
        $partstring = strstr($results, '[');
        $length = strlen($partstring);
        $jsonstring = substr($partstring, 0, $length - 2);

        /* Decode the JSON string */
        $resultarray = $this->modx->fromJSON($jsonstring);

        return $resultarray;

    }

    /**
     * Check an imported file can be created
     *
     * @access private
     * @param $& file contents for lrngth check
     *
     */
    function _fileCreateCheck(&$filecontents) {

        $errorstring = '';

        if ( !is_writeable($this->_importpath) ) {

            $errorstring = $this->modx->lexicon('importfoldernotwriteable');
            return $errorstring;
        }

        if ( strlen($filecontents) == 0 ) {

            $errorstring = $this->modx->lexicon('importfilenolength');
            return $errorstring;
        }

        return $errorstring;

   }

   /**
     * Check that the evolution gateway code exists
     *
     * @access private
     * @return true indicates the gateway is in place
     *
     */
    function _evolutionGatewayExists(&$status) {

        $url = $this->_connectorURL."/assets/snippets/revogateway/index.php";
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);
        $result = curl_exec($this->_curlSession);

        /* Result length is constant, check for it */
        if ( strlen($result) != Provisioner::GATEWAY_INSTALLED ) return false;
        
        $resultarray = $this->modx->fromJSON($result);
        
        /* Check for success */
        if ( $resultarray['success'] == 1 ) return true;

        return false;

   }


}
