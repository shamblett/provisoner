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
     * @var loggedin logged in user
     * @access private
     */
    private $_loggedinUser;

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
     * @var importpath the path to import files
     * @access private
     */
    private $_importpath;
    
    /**
     * @var tmppath the path to the tmp directory
     * @access private
     */
    private $_tmppath;

    /**
     * @var Evolution import log file
     * @access private
     */
    private $_log;

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

        /* Get our tmp path for CURL usage */
        $this->_tmppath = $this->modx->getOption('assets_path').'components/provisioner/tmp/';

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
		
         /* Check for logged in user same as this one */
        if ( $this->_checkLoggedIn() ) {

            $error = $this->modx->lexicon('wronguser');
            return $status;
	}

        $this->_connectorURL = $url;

        /* CURL initialisation */

        /* Create a temp file for session cookies */
        $this->_cookiefile = tempnam($this->_tmppath, "CURL");

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
        $user = $this->modx->getLoginUserName();
        if ( $remoteSiteTypeIsEvo ) {

            if ( strstr($result, 'Location') === FALSE ) {

                $error = $result;

            } else {

                $status = true;
                $this->_setSessionParams($this->_cookiefile,
                        $this->_connectorURL, $account, true, $siteId, $user );
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
                            $this->_connectorURL, $account, false, $siteId, $user );

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
                            $this->_connectorURL, $account, false, $siteId, $user );

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
        if ( $this->_checkLoggedIn() ) {

            $error = $this->modx->lexicon('wronguser');
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
        if ( $this->_checkLoggedIn() ) {

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
            $result = $this->_getTreeResources($id, $convert, $errorstring);

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

        $menu = array();

        /* Logged in check */
        if ( $this->_checkLoggedIn() ) {

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
        $menu[0] = array(
                'id' => 'pv-import-file',
                'text' => $this->modx->lexicon('import_file'),
                'handler' => 'function(itm,e) {
                        this.importFile(itm,e);
                    }'
        );

        /* If an evo site we can import binary files as they are encoded,
         * if revo we have to check if the 'page' node attribute is null indicating
         * a binary file.
         */
        foreach ( $resultarray as &$jsonresult) {

            $jsonresult['menu'] = array('items' => $menu);
            if ( !$this->_remoteIsEvo ) {

                if ( $jsonresult['page'] == '' ) {

                    $jsonresult['menu'] = array('items' => '');
                }
            }
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
        if ( $this->_checkLoggedIn() ) {

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
        if ( $this->_checkLoggedIn() ) {

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
        if ( $this->_checkLoggedIn() ) {

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
        /* Decode from base64 if from evolution */
        if ( $this->_remoteIsEvo ) $filecontents = base64_decode($filerecord['content']);
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

            if ( $type == 'tv') {
                $translator->translate($$elementdata['default_text']);
                $translator->translate($$elementdata['display']);
                $translator->translate($$elementdata['display_params']);
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

        /* Change unix timestamps into date strings for xPDO */
        $resourcedata['pub_date'] = date("Y-m-d H:i:s" ,  $resourcedata['pub_date']);
        $resourcedata['unpub_date'] = date("Y-m-d H:i:s" ,  $resourcedata['unpub_date']);
        $resourcedata['createdon'] = date("Y-m-d H:i:s" ,  $resourcedata['createdon']);
        $resourcedata['editedon'] = date("Y-m-d H:i:s" ,  $resourcedata['editedon']);
        $resourcedata['deletedon'] = date("Y-m-d H:i:s" ,  $resourcedata['deletedon']);
        $resourcedata['publishedon'] = date("Y-m-d H:i:s" ,  $resourcedata['publishedon']);

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

    function _getTreeResources($id, $convert, &$errorstring ) {

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

            /* Change unix timestamps into date strings for xPDO */
            $resourcedata['pub_date'] = date("Y-m-d H:i:s" ,  $resourcedata['pub_date']);
            $resourcedata['unpub_date'] = date("Y-m-d H:i:s" ,  $resourcedata['unpub_date']);
            $resourcedata['createdon'] = date("Y-m-d H:i:s" ,  $resourcedata['createdon']);
            $resourcedata['editedon'] = date("Y-m-d H:i:s" ,  $resourcedata['editedon']);
            $resourcedata['deletedon'] = date("Y-m-d H:i:s" ,  $resourcedata['deletedon']);
            $resourcedata['publishedon'] = date("Y-m-d H:i:s" ,  $resourcedata['publishedon']);

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

                /* Site Identifier */
                $siteId = $this->modx->getObject('modSystemSetting',
                        array ('key' => 'siteid',
                        'namespace' => 'provisioner'));

                $this->_siteId = $siteId->get('value');

                /* Logged in User */
                $user = $this->modx->getObject('modSystemSetting',
                        array ('key' => 'user',
                        'namespace' => 'provisioner'));

                $this->_loggedinUser = $user->get('value');

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
     * @param $user Logged in user
     *
     */
    function _setSessionParams($cookiefile, $url, $account, $evolution, $siteId, $user) {

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

        /* User */
        $usersetting = $this->modx->getObject('modSystemSetting',
                array ('key' => 'user',
                'namespace' => 'provisioner'));

        $usersetting->set('value', $user);
        $usersetting->save();

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
    function _evolutionGatewayExists() {

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

   /**
     * Check that no other user is already logged in
     *
     * @access private
     * @return true another user is already logged in
     *
     */
   function _checkLoggedIn() {

       $user = $this->modx->getLoginUserName();
       if ( $this->_loggedin) {

           if ( $user != $this->_loggedinUser ) return true;
       }

       return false;
   }

   /**
     * Evolution site import
     *
     * @access public
     * @param $importArray elements to import
     * @param $context the context to import into
     * @param $parent Re-parent imported categories to Provisioner
     * @param $smartmode be smart!
     * @param $stoponerror stop if we encounter an error
     * @param $errorstring reported error string
     *
     */
   function importEvoSite($importArray, $context, $parent, $abort, $timeout, &$errorstring) {

        $result = false;

        /* 'Have' flags */
        $getCategories = false;
        $haveResources = false;
        $haveTemplates = false;
        $haveTvs = false;
        $haveSnippets = false;
        $haveChunks = false;
        $havePlugins = false;
        $haveCategories = false;
        $haveKeywords = false;
        $haveMetatags = false;
        $haveDocgroups = false;
        $haveTvAccess = false;
        $haveTvContent = false;
        $haveTvTemplate = false;
        $havePluginEvent = false;

        /* Map arrays, these hold old -> new id mappings, indexed by old */
        $categoryMap = array();
        $templateMap = array();
        $resourceMap = array();
        $docgroupMap = array();
        $tvMap = array();
        $pluginMap = array();

        /* Logged in check */
        if ( !$this->_loggedin ) {

            $errorstring = $this->modx->lexicon('notloggedin');
            return false;
        }
        
        /* Evolution site check */
        if ( !$this->_remoteIsEvo ) {

            $errorstring = $this->modx->lexicon('notevosite');
            return false;
        }

        /* Abort check */
        if ( $abort ) {
            $errorstring = $this->modx->lexicon('evoimportaborted');

            /* Return a successful abort! */
            return true;
        }

        /* Set the time limit, or try to */
        set_time_limit($timeout);
        
        /* Set the base include flags */
        $smartmode = true;
        $deletebefore = true;
        
        /* Load a tag translator */
        $this->modx->loadClass('modParser095', '', false, true);
        $translator= new modParser095($this->modx);

        /*
         * Importation of requested elements starts here
        */

        /* Open logging */
        $this->_initialiseImportLog();

        /* Get all resources if asked for */
        if ( $importArray['resources'] ) {

            $this->_importLogHeader("Getting resources .........");

            $result = $this->_getAllEvoResources($evoResources, $evoKeywords,
                                                 $evoMetatags, $evoDocgroups,
                                                 $smartmode, $errorstring);
            if ( !$result ) return false;

            /* Set the 'have' flags */
            $resourceNo = count($evoResources);
            $keywordsNo = count($evoKeywords);
            $metatagsNo = count($evoMetatags);
            $docGroupsNo = count($evoDocgroups);

            if ( $resourceNo != 0 ) $haveResources = true;
            if ( $keywordsNo != 0 ) $haveKeywords = true;
            if ( $metatagsNo != 0 ) $haveMetatags = true;
            if ( $docGroupsNo != 0 ) $haveDocgroups = true;

            $this->_importLog("Got $resourceNo resources");
            $this->_importLog("Got $keywordsNo keywords");
            $this->_importLog("Got $metatagsNo metatags");
            $this->_importLog("Got $docGroupsNo docgroups");
        }
        
        /* Get all templates if asked for */
        if ( $importArray['templates'] ) {

            $this->_importLogHeader("Getting templates .........");

            $result = $this->_getAllEvoElements($evoTemplates, "template", $errorstring);
            if ( !$result ) return false;

            /* Set the 'have' flag */
            $templateNo = count($evoTemplates);
            if ( $templateNo != 0 ) $haveTemplates = true;
            $this->_importLog("Got $templateNo templates");

            /* if we have this element we need categories */
            $getCategories = true;
        }

        /* Get all tv's if asked for */
        if ( $importArray['tvs'] ) {

            $this->_importLogHeader("Getting TV's .........");

            $result = $this->_getAllEvoElements($evoTvs, "tv", $errorstring);
            if ( !$result ) return false;
            
            /* Set the 'have' flag */
            $tvNo = count($evoTvs);
            if ( $tvNo != 0 ) $haveTvs = true;
            $this->_importLog("Got $tvNo TV's");
            
            /* If smart mode is on get the associated data */
            if ( $smartmode ) {

                $this->_importLogHeader("Getting associated TV data .........");

                $result = $this->_getAllEvoTVData($evoTvAccess, $evoTvTemplate,
                                                  $evoTvContent, $errorstring);

                $tvAccessNo = count($evoTvAccess);
                $tvTemplateNo = count($evoTvTemplate);
                $tvContentNo = count($evoTvContent);

                if ( $tvAccessNo != 0 ) $haveTvAccess = true;
                if ( $tvTemplateNo != 0 ) $haveTvTemplate = true;
                if ( $tvContentNo != 0 ) $haveTvContent = true;

                $this->_importLog("Got $tvAccessNo TV -> resource group records");
                $this->_importLog("Got $tvTemplateNo TV -> template records");
                $this->_importLog("Got $tvContentNo TV -> resource records");
                
            }

            /* if we have this element we need categories */
            $getCategories = true;
        }

        /* Get all snippets if asked for */
        if ( $importArray['snippets'] ) {

            $this->_importLogHeader("Getting snippets .........");

            $result = $this->_getAllEvoElements($evoSnippets, "snippet", $errorstring);
            if ( !$result ) return false;

             /* Set the 'have' flag */
            $snippetNo = count($evoSnippets);
            if ( $snippetNo != 0 ) $haveSnippets = true;
            $this->_importLog("Got $snippetNo snippets");

            /* if we have this element we need categories */
            $getCategories = true;
        }

        /* Get all chunks if asked for */
        if ( $importArray['chunks'] ) {

            $this->_importLogHeader("Getting chunks .........");

            $result = $this->_getAllEvoElements($evoChunks, "chunk", $errorstring);
            if ( !$result ) return false;

             /* Set the 'have' flag */
            $chunkNo = count($evoChunks);
            if ( $chunkNo != 0 ) $haveChunks = true;
            $this->_importLog("Got $chunkNo chunks");

            /* if we have this element we need categories */
            $getCategories = true;
        }

        /* Get all plugins if asked for */
        if ( $importArray['plugins'] ) {

            $this->_importLogHeader("Getting plugins .........");

            $result = $this->_getAllEvoElements($evoPlugins, "plugin", $errorstring);
            if ( !$result ) return false;

             /* Set the 'have' flag */
            $pluginNo = count($evoPlugins);
            if ( $pluginNo != 0 ) $havePlugins = true;
            $this->_importLog("Got $pluginNo plugins");

            /* If smart mode is on get the associated data */
            if ( $smartmode ) {

                $this->_importLogHeader("Getting plugin events.........");

                $result = $this->_getAllEvoPluginEvents($evoPluginEvent, $evoPluginEventMap,
                                                        $errorstring);
                
                $pluginEventNo = count($evoPluginEventMap);
                if ( $pluginEventNo != 0 ) $havePluginEvent = true;
                $this->_importLog("Got $pluginEventNo plugin events");

            }

            /* if we have this element we need categories */
            $getCategories = true;
        }

        /* Get all categories if we need them */
        if ( $getCategories ) {

            $this->_importLogHeader("Getting categories .........");

            $result = $this->_getAllEvoElements($evoCategories, "category", $errorstring);
            if ( !$result ) return false;

            $categoryNo = count($evoCategories);
            if ( $categoryNo != 0 ) $haveCategories = true;
            $this->_importLog("Got $categoryNo categories");
        }

        /*
         * Creation processing starts here
        */


        /* Firstly if we have categories we need them, so create them */
        if ( $haveCategories ) {

            $this->_importLogHeader("Creating categories .........");

            /* Delete existing if requested */
            if ( $deletebefore ) {
                
                $result = $this->_deleteElements('modCategory', $errorstring);
                if ( !$result ) return false;              
            }

            /* Create */
            foreach ($evoCategories as $category ) {

                /* Re parent if requested */
                if ( $parent ) $category['parent'] = $this->_category;

                /* Create them */
                $categoryObject = $this->modx->newObject('modCategory');
                $categoryObject->fromArray($category);
                if ($categoryObject->save() == false) {

                        $errorstring = $this->modx->lexicon('evoimportcatfail');
                        $errorstring .= $category['category'];
                        return false;
                }

                $name = $category['category'];
                $this->_importLog("Created category $name ");

                /* Update the map */
                $categoryMap[$category['id']] = $categoryObject->get('id');
            }
        }

        /* Next, get the templates if needed and assign them the new categories */
        if ( $haveTemplates ) {

             $this->_importLogHeader("Creating templates .........");

            /* Delete existing if requested */
            if ( $deletebefore ) {

                $result = $this->_deleteElements('modTemplate', $errorstring);
                if ( !$result ) return false;
            }

            foreach ($evoTemplates as $template ) {

                 $template['category'] = $categoryMap[$template['category']];

                 /* Tag convert */
                 $translator->translate($template['content']);

                 /* Create them */
                 $templateObject = $this->modx->newObject('modTemplate');
                 $templateObject->fromArray($template);
                 if ($templateObject->save() == false) {

                        $errorstring = $this->modx->lexicon('evoimporttemplatefail');
                        $errorstring .= $template['templatename'];
                        return false;
                }

                $name = $template['templatename'];
                $this->_importLog("Created template $name ");

                /* Update the map */
                $templateMap[$template['id']] = $templateObject->get('id');

             }

        }

        /* Next, get the resources if needed and assign them the new templates
         * if we have them, then re-parent the resources into the local tree.
         * Then check for smart mode, if 'on' we need to process keywords and the
         * document xref, metatags and document groups and their xref's.
         */
        if ( $haveResources ) {

            $this->_importLogHeader("Creating resources .........");

            /* Delete existing if requested */
            if ( $deletebefore ) {

                $result = $this->_deleteElements('modResource', $errorstring);
                if ( !$result ) return false;
            }

            foreach ($evoResources as $resource ) {

                /* Map to the new templates if we have them */
                if ( $haveTemplates ) $resource['template'] = $templateMap[$resource['template']];

                /* Set the context key */
                $resource['context_key'] = $context;
                
                /* Change unix timestamps into date strings for xPDO */
                $resource['pub_date'] = date("Y-m-d H:i:s" ,  $resource['pub_date']);
                $resource['unpub_date'] = date("Y-m-d H:i:s" ,  $resource['unpub_date']);
                $resource['createdon'] = date("Y-m-d H:i:s" ,  $resource['createdon']);
                $resource['editedon'] = date("Y-m-d H:i:s" ,  $resource['editedon']);
                $resource['deletedon'] = date("Y-m-d H:i:s" ,  $resource['deletedon']);
                $resource['publishedon'] = date("Y-m-d H:i:s" ,  $resource['publishedon']);

                /* Tag convert */
                $translator->translate($resource['content']);
                $translator->translate($resource['pagetitle']);
                $translator->translate($resource['longtitle']);

                /* Create them */
                 $resourceObject = $this->modx->newObject('modResource');
                 $resourceObject->fromArray($resource);
                 if ($resourceObject->save() == false) {

                        $errorstring = $this->modx->lexicon('evoimportresourcefail');
                        $errorstring .= $resource['pagetitle'];
                        return false;
                }

                $name = $resource['pagetitle'];
                $this->_importLog("Created resource $name ");

                /* Reset the newly created id to the original one, use
                 * exec to do this, not xPDO
                 */
                $newId = $resourceObject->get('id');
                $oldId = $resource['id'];
                $tableName = $this->modx->getTableName('modResource');
                $sql = "UPDATE $tableName SET `id` = $oldId WHERE `id` = $newId";
                $this->modx->exec($sql);
                
                
                /* Update the map, redundant with re-linking of the id
                 * but we'll keep the mechanism in place for now 
                 */
                $resourceMap[$resource['id']] = $resource['id'];
         
            }
            
            /* Check for smart mode */
            if ( $smartmode ) {

                /* Keywords */
                if ( $haveKeywords ) {

                    /* Delete existing if requested */
                    if ( $deletebefore ) {

                        $result = $this->_deleteElements('modKeyword', $errorstring);
                        if ( !$result ) return false;
                    }
                    
                    foreach ( $evoKeywords as $name => $resourcelist ) {

                        /* Create */
                        $keywordObject = $this->modx->newObject('modKeyword',
                                                                array('keyword' => $name));

                        /* Add the resources */
                        foreach ( $resourcelist as $xref ) {

                            if ( $xref == null ) continue;
                            /* Map the old resource id and get the new resource */
                            $newResourceId = $resourceMap[$xref];
                            if ( $newResourceId == null ) continue;                          
                            $newResourceObject = $this->modx->getObject('modResource',
                                                                        array('id' => $newResourceId));
                            if ( $newResourceObject == null ) continue;                                            
                            $keywordObject->addMany($newResourceObject);
                        }

                        /* Save it */
                        $keywordObject->save();
                    }
                    
                } // Have keywords

                /* Metatags */
                if ( $haveMetatags ) {

                    /* Delete existing if requested */
                    if ( $deletebefore ) {

                        $result = $this->_deleteElements('modMetatag', $errorstring);
                        if ( !$result ) return false;
                    }

                    foreach ( $evoMetatags as $metatag ) {

                        /* Create */
                        $metatagObject = $this->modx->newObject('modMetatag', $metatag);
                        if ( $metatagObject == null ) continue;
                        $metatagObject->save();

                    }

                } // Have metatags

                /* Document groups */
                if ( $haveDocgroups ) {

                    /* Delete existing if requested */
                    if ( $deletebefore ) {

                        $result = $this->_deleteElements('modResourceGroup', $errorstring);
                        if ( !$result ) return false;
                        $result = $this->_deleteElements('modResourceGroupResource', $errorstring);
                        if ( !$result ) return false;
                    }

                    /* Create the new ones */
                    foreach ( $evoDocgroups[0] as $name ) {

                        /* Create */
     
                        /* Existence check */
                        $alreadyExists = $this->modx->getObject('modResourceGroup',array('name' => $name['name']));
                        if ( !$alreadyExists) {
                            $docgroupObject = $this->modx->newObject('modResourceGroup',$name);
                            $docgroupObject->save();

                            /* Update the map */
                            $docgroupMap[$name['id']] = $docgroupObject->get('id');

                            /* Add the resources */
                            foreach ( $evoDocgroups[1][$name['name']] as $xref ) {

                                if ( $xref == null ) continue;
                                /* Map the old resource id and get the new resource */
                                $newResourceId = $resourceMap[$xref];
                                if ( $newResourceId == null ) continue;
                                $resourceGroupResource = $this->modx->newObject('modResourceGroupResource');
                                $resourceGroupResource->set('document',$newResourceId);
                                $resourceGroupResource->set('document_group',$docgroupObject->get('id'));
                                $resourceGroupResource->save();
                            }
                        }
                    }

                } // Have document groups
             
            } // Smart mode

       } // Have resources

       /* Next, get the TV's if needed and assign them the new categories */
        if ( $haveTvs ) {

            $this->_importLogHeader("Creating TV's .........");

            /* Delete existing if requested */
            if ( $deletebefore ) {

                $result = $this->_deleteElements('modTemplateVar', $errorstring);
                if ( !$result ) return false;
            }

            foreach ($evoTvs as $tv ) {

                 $tv['category'] = $categoryMap[$tv['category']];

                 /* Tag convert */
                 $translator->translate($tv['default_text']);
                 $translator->translate($tv['display']);
                 $translator->translate($tv['display_params']);

                 /* Create them */
                 $tvObject = $this->modx->newObject('modTemplateVar');
                 $tvObject->fromArray($tv);
                 if ($tvObject->save() == false) {

                        $errorstring = $this->modx->lexicon('evoimporttvfail');
                        $errorstring .= $tv['name'];
                        return false;
                }

                $name = $tv['name'];
                $this->_importLog("Created TV $name ");

                /* Update the map */
                $tvMap[$tv['id']] = $tvObject->get('id');

             }

        } // Have TV's

        /* If smart mode is on create the associated data */
        if ( $smartmode ) {

            /* Tv template mapping */
            if ( $haveTvTemplate ) {

                /* Delete existing if requested */
                if ( $deletebefore ) {

                    $result = $this->_deleteElements('modTemplateVarTemplate', $errorstring);
                    if ( !$result ) return false;
                }

                /* Create */
                foreach ( $evoTvTemplate as $tvTemplate ) {

                    $tvTemplateObject = $this->modx->newObject('modTemplateVarTemplate');
                    /* Re-map the template and the TV id */
                    $tvTemplate['templateid'] = $templateMap[$tvTemplate['templateid']];
                    if ( $tvTemplate['templateid'] == null ) continue;
                    $tvTemplate['tmplvarid'] = $tvMap[$tvTemplate['tmplvarid']];
                    if ( $tvTemplate['tmplvarid'] == null ) continue;
                    $tvTemplateObject->fromArray($tvTemplate, '' , true);
                    if ($tvTemplateObject->save() == false) {

                        $errorstring = $this->modx->lexicon('evoimporttvtemplatefail');
                        $errorstring .= $tvTemplate['tmplvarid'];
                        return false;
                    }

                }

            } // Have TV templates

            /* Tv resource mapping */
            if ( $haveTvContent ) {

                /* Delete existing if requested */
                if ( $deletebefore ) {

                    $result = $this->_deleteElements('modTemplateVarResource', $errorstring);
                    if ( !$result ) return false;
                }

                /* Create */
                foreach ( $evoTvContent as $tvContent ) {

                    $tvContentObject = $this->modx->newObject('modTemplateVarResource');
                    /* Re-map the resource and the TV id */
                    $tvContent['contentid'] = $resourceMap[$tvContent['contentid']];
                    if ( $tvContent['contentid'] == null ) continue;
                    $tvContent['tmplvarid'] = $tvMap[$tvContent['tmplvarid']];
                    if ( $tvContent['tmplvarid'] == null ) continue;
                    $tvContentObject->fromArray($tvContent, '' , true);
                    if ($tvContentObject->save() == false) {

                        $errorstring = $this->modx->lexicon('evoimporttvcontentfail');
                        $errorstring .= $tvContent['tmplvarid'];
                        return false;
                    }

                }

            } // TV resource

            /* Tv resource group mapping */
            if ( $haveTvAccess ) {

                /* Delete existing if requested */
                if ( $deletebefore ) {

                    $result = $this->_deleteElements('modTemplateVarResourceGroup', $errorstring);
                    if ( !$result ) return false;
                }

                /* Create */
                foreach ( $evoTvAccess as $tvAccess ) {

                    $tvAccessObject = $this->modx->newObject('modTemplateVarResourceGroup');
                    /* Re-map the resource group and the TV id */
                    $tvAccess['documentgroup'] = $docgroupMap[$tvAccess['documentgroup']];
                    if ( $tvAccess['documentgroup'] == null ) continue;
                    $tvAccess['tmplvarid'] = $tvMap[$tvAccess['tmplvarid']];
                    if ( $tvAccess['tmplvarid'] == null ) continue;
                    $tvAccessObject->fromArray($tvAccess);
                    if ($tvAccessObject->save() == false) {

                        $errorstring = $this->modx->lexicon('evoimporttvaccessfail');
                        $errorstring .= $tvAccess['tmplvarid'];
                        return false;
                    }

                }

            } // TV resource

       } // TV smart mode

        /* Next, get the snippets if needed and assign them the new categories */
        if ( $haveSnippets ) {

            $this->_importLogHeader("Creating snippets .........");

            /* Delete existing if requested */
            if ( $deletebefore ) {

                $result = $this->_deleteElements('modSnippet', $errorstring);
                if ( !$result ) return false;
            }

            foreach ($evoSnippets as $snippet ) {

                 $snippet['category'] = $categoryMap[$snippet['category']];

                 /* Create them */
                 $snippetObject = $this->modx->newObject('modSnippet');
                 /* Remove any : characters */
                 $snippet['name'] = str_replace(':', '-', $snippet['name']);
                 $snippetObject->fromArray($snippet);
                 if ($snippetObject->save() == false) {

                        $errorstring = $this->modx->lexicon('evoimportsnippetfail');
                        $errorstring .= $snippet['name'];
                        return false;
                }

                $name = $snippet['name'];
                $this->_importLog("Created snippet $name ");

             }

        } // Have snippets

         /* Next, get the chunks if needed and assign them the new categories */
        if ( $haveChunks ) {

            $this->_importLogHeader("Creating chunks .........");

            /* Delete existing if requested */
            if ( $deletebefore ) {

                $result = $this->_deleteElements('modChunk', $errorstring);
                if ( !$result ) return false;
            }

            foreach ($evoChunks as $chunk ) {

                 $chunk['category'] = $categoryMap[$chunk['category']];

                 /* Tag convert */
                 $translator->translate($chunk['snippet']);
                 
                 /* Create them */
                 $chunkObject = $this->modx->newObject('modChunk');
                 $chunkObject->fromArray($chunk);
                 if ($chunkObject->save() == false) {

                        $errorstring = $this->modx->lexicon('evoimportchunkfail');
                        $errorstring .= $chunk['name'];
                        return false;
                }

                $name = $chunk['name'];
                $this->_importLog("Created chunk $name ");

             }

        } // Have chunks

        /* Next, get the plugins if needed and assign them the new categories */
        if ( $havePlugins ) {

            $this->_importLogHeader("Creating plugins .........");

            /* Delete existing if requested */
            if ( $deletebefore ) {

                $result = $this->_deleteElements('modPlugin', $errorstring);
                if ( !$result ) return false;
            }

            foreach ($evoPlugins as $plugin ) {

                 $plugin['category'] = $categoryMap[$plugin['category']];

                 /* Create them */
                 $pluginObject = $this->modx->newObject('modPlugin');
                 $pluginObject->fromArray($plugin);
                 if ($pluginObject->save() == false) {

                        $errorstring = $this->modx->lexicon('evoimportpluginfail');
                        $errorstring .= $plugin['name'];
                        return false;
                }

                $name = $plugin['name'];
                $this->_importLog("Created plugin $name ");

                /* Update the map */
                $pluginMap[$plugin['id']] = $pluginObject->get('id');

             }  

            /* If smart mode is on create the associated data */
            if ( $smartmode ) {

                /* Plugin events */
                if ( $havePluginEvent ) {

                    /* Delete existing if requested */
                    if ( $deletebefore ) {

                        $result = $this->_deleteElements('modPluginEvent', $errorstring);
                        if ( !$result ) return false;
                    }

                    /* Create the events */
                    foreach ( $evoPluginEventMap as $eventMap ) {

                        /* Check we have a local event of the same name */
                        $eventName = $evoPluginEvent[$eventMap['evtid']];
                        $c = $this->modx->newQuery('modEvent');
                        $c->where(array('name' => $eventName['name']));
                        $localEvent = $this->modx->getObject('modEvent',$c);
                        if ( $localEvent ) {

                            /* Yes, create the event map */
                            $pluginId = $pluginMap[$eventMap['pluginid']];
                            $eventMap['pluginid'] = $pluginId;
                            $eventMap['event'] = $eventName['name'];
                            $eventMapObject = $this->modx->newObject('modPluginEvent');
                            $eventMapObject->fromArray($eventMap, '', true);
                            if ($eventMapObject->save() == false) {

                                $errorstring = $this->modx->lexicon('evoimportplugineventfail');
                                $errorstring .= $pluginId;
                                return false;
                            }
                        }
                    }
                    
                } // Have plugin events
                    
            } // Plugin smartmode

        } // Have plugins

        /* Clear the cache */
        $this->_importLogHeader("Clearing the site cache .........");
        $contexts = $this->modx->getCollection('modContext');
        foreach ($contexts as $context) {
            $paths[] = $context->get('key') . '/';
         }
         $options = array(
            'publishing' => 1,
            'extensions' => array('.cache.php', '.msg.php', '.tpl.php'),
         );
         if ($this->modx->getOption('cache_db')) $options['objects'] = '*';
         $this->modx->cacheManager->clearCache($paths, $options);

         /* Done, exit */
         $this->_closeImportLog();
         return true;
       
   }

   /**
     * Evolution site import resources
     *
     * @access private
     * @param $evoResources the imported resources
     * @param $evoKeywords the imported resources keywords
     * @param $evoMetatags the imported resources metatags
     * @param $evoDocgroups the imported docgroups and resource mapping
     * @param $smartmode smart mode
     * @param $errorstring reported error string
     *
     */
   function _getAllEvoResources(&$evoResources, &$evoKeywords, &$evoMetatags,
                                &$evoDocgroups, $smartmode, &$errorstring) {

       $resourceArray = array();
       $keywordArray = array();
       $metatagArray = array();
       $docgroupArray = array();

       $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getall&entity=resources";
       curl_setopt($this->_curlSession, CURLOPT_URL, $url);
       $result = curl_exec($this->_curlSession);

       /* Decode the result */
       $resourceArray = $this->modx->fromJSON($result);

       /* Check if we succeeded */
       if ( $resourceArray['success'] != 1 ) {

            $errorstring = $this->modx->lexicon('failedtogetremoteresource')." ".$resourceArray['message'];
            return false;
        }

        /* Assign the resources */
        $evoResources = $resourceArray['object'];

        /* If not in smart mode return here */
        if ( !$smartmode ) return true;
        
        /* Get the keywords and the Xref data */
        $this->_importLogHeader("Getting keywords and xref .........");
        $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getall&entity=keywords";
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);
        $result = curl_exec($this->_curlSession);

        /* Decode the result */
       $keywordArray = $this->modx->fromJSON($result);

       /* Check if we succeeded */
       if ( $keywordArray['success'] != 1 ) {

            $errorstring = $this->modx->lexicon('failedtogetremotekeywords')." ".$keywordArray['message'];
            return false;
        }

        /* Assign the keywords */
        $evoKeywords = $keywordArray['object'];
        
        /* Get the metatag data */
        $this->_importLogHeader("Getting metatags .........");
        $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getall&entity=metatags";
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);
        $result = curl_exec($this->_curlSession);

        /* Decode the result */
       $metatagArray = $this->modx->fromJSON($result);

       /* Check if we succeeded */
       if ( $metatagArray['success'] != 1 ) {

            $errorstring = $this->modx->lexicon('failedtogetremotemetatags')." ".$metatagArray['message'];
            return false;
        }

        /* Assign the metatags */
        $evoMetatags = $metatagArray['object'];

        /* Get the document groups and the assigned resources */
        $this->_importLogHeader("Getting docgroups .........");
        $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getall&entity=docgroups";
        curl_setopt($this->_curlSession, CURLOPT_URL, $url);
        $result = curl_exec($this->_curlSession);

        /* Decode the result */
       $docgroupArray = $this->modx->fromJSON($result);

        /* Check if we succeeded */
       if ( $docgroupArray['success'] != 1 ) {

            $errorstring = $this->modx->lexicon('failedtogetremotedocgroups')." ".$docgroupArray['message'];
            return false;
        }

        /* Assign the docgroups */
        $evoDocgroups = $docgroupArray['object'];

        /* Ok, exit */
        return true;
   }

   /**
     * Evolution site import TV data
     *
     * @access private
     * @param $evoTVAccess the imported access rights
     * @param $evoTVTemplate  the imported TV template mapping
     * @param $evoTVContent the imported TV content
     * @param $errorstring reported error string
     *
     */
   function _getAllEvoTVData(&$evoTVAccess, &$evoTVTemplate,
                             &$evoTVContent, &$errorstring) {

       $resultArray = array();

       /* Get the tv data */
       $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getalltvdata&entity=elements";
       curl_setopt($this->_curlSession, CURLOPT_URL, $url);
       $result = curl_exec($this->_curlSession);

       /* Decode the result */
       $resultArray = $this->modx->fromJSON($result);

       /* Check if we succeeded */
       if ( $resultArray['success'] != 1 ) {

            $errorstring = $this->modx->lexicon('failedtogetremotetvdata')." ".$resultArray['message'];
            return false;
        }

        /* Assign the elements */
        $evoTVAccess = $resultArray['object'][0];
        $evoTVTemplate = $resultArray['object'][1];
        $evoTVContent = $resultArray['object'][2];

        return true;

   }


    /**
     * Evolution site import plugin events
     *
     * @access private
     * @param $evoPluginEvents the imported plugin event names
     * @param $evoPluginEventMap the imported plugin event map
     * @param $errorstring reported error string
     *
     */
   function _getAllEvoPluginEvents(&$evoPluginEvents, &$evoPluginEventMap,
                                   &$errorstring) {

       $resultArray = array();

       /* Get the plugin data */
       $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getallpluginevents&entity=elements";
       curl_setopt($this->_curlSession, CURLOPT_URL, $url);
       $result = curl_exec($this->_curlSession);

       /* Decode the result */
       $resultArray = $this->modx->fromJSON($result);

       /* Check if we succeeded */
       if ( $resultArray['success'] != 1 ) {

            $errorstring = $this->modx->lexicon('failedtogetremotepluginevents')." ".$resultArray['message'];
            return false;
        }

        /* Assign the elements */
        $evoPluginEvents = $resultArray['object'][0];
        $evoPluginEventMap = $resultArray['object'][1];

        return true;

   }

   /**
     * Evolution site import elements
     *
     * @access private
     * @param $evoElements the imported elements
     * @param $errorstring reported error string
     *
     */
   function _getAllEvoElements(&$evoElements, $type, &$errorstring) {

       $elementArray = array();

       $url = $this->_connectorURL."/assets/snippets/revogateway/connectors/index.php?action=getall&entity=elements&type=$type";
       curl_setopt($this->_curlSession, CURLOPT_URL, $url);
       $result = curl_exec($this->_curlSession);

       /* Decode the result */
       $elementArray = $this->modx->fromJSON($result);

       /* Check if we succeeded */
       if ( $elementArray['success'] != 1 ) {

            $errorstring = $this->modx->lexicon('failedtogetremoteelement')." ".$elementArray['message'];
            return false;
        }

        /* Assign the elements */
        $evoElements = $elementArray['object'];
        return true;
   }

   /**
     * Delete elements
     *
     * @access private
     * @param $classname the MODx classname to delete
     * @param $errorstring reported error string
     *
     */
   function _deleteElements($classname, &$errorstring) {

        $result = $this->modx->removeCollection($classname, null);
        if ( $result === false ) {

            $errorstring = $this->modx->lexicon('failedtoremovelements') . $classname;
            return false;
        }

        return true;

   }

   /**
     * Initialise evo site import logging
     *
     * @access private
     *
     */
   function _initialiseImportLog() {

       $datePart = date('d-m-y-Hi');
       $outFile = $this->_tmppath . 'evoimport-' . $datePart . '.log';
       $this->log = fopen($outFile,"w");
       fwrite($this->log, PHP_EOL);
       fwrite($this->log, "--------------------------------------");
       fwrite($this->log, PHP_EOL);
       fwrite($this->log, "Importing from $this->_connectorURL");
       fwrite($this->log, PHP_EOL);
       fwrite($this->log, "Import started at - " . $datePart);
       fwrite($this->log, PHP_EOL);
       fwrite($this->log, "--------------------------------------");
       fwrite($this->log, PHP_EOL);

   }

   /**
     * Log evo site import actions
     *
     * @access private
     *
     * @param $log log line text
     */

   function _importLog($log) {

       $datePart = date('H:i:s');
       fwrite($this->log, $datePart . "  :  ". $log);
       fwrite($this->log, PHP_EOL);

   }

   /**
     * Log evo site import actions
     *
     * @access private
     *
     * @param $log log line text
     */

   function _importLogHeader($log) {

       $datePart = date('H:i:s');
       fwrite($this->log, PHP_EOL);
       fwrite($this->log, $datePart . "  :  >>>>". $log);
       fwrite($this->log, PHP_EOL);
       fwrite($this->log, PHP_EOL);

   }
   /**
     * Terminate evo site import logging
     *
     * @access private
     *
     */
   function _closeImportLog() {

       $datePart = date('d-m-y-Hi');
       fwrite($this->log, PHP_EOL);
       fwrite($this->log, "--------------------------------------");
       fwrite($this->log, PHP_EOL);
       fwrite($this->log, "Import completed at - " . $datePart);
       fwrite($this->log, PHP_EOL);
       fwrite($this->log, "--------------------------------------");
       fwrite($this->log, PHP_EOL);
       fclose($this->log);

   }

        
}
