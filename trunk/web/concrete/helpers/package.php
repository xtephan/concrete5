<?
/**
 * @package Helpers
 * @category Concrete
 * @author Todd Crowe <toddbcrowe@gmail.com>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

/**
 * Functions useful for downloading and install marketplace packages
 * @package Helpers
 * @category Concrete
 * @author Todd Crowe <toddbcrowe@gmail.com>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

defined('C5_EXECUTE') or die(_("Access Denied."));

class PackageHelper {

	public function get_remote_url($type, $remoteCID) {
		if (empty($remoteCID)) {
			return "";
		}

	    if ($type != 'theme') {
	    	$helper = Loader::helper('concrete/marketplace/blocks');
    		$list = $helper->getCombinedList();
		} else {
	    	$helper = Loader::helper('concrete/marketplace/themes');
    		$list = $helper->getPreviewableList();
		}
        foreach ($list as $item) {
			if ($remoteCID == $item->getRemoteCollectionID()) {
				break;
			}
		}
		if (empty($item)) {
			return "";
		}

		$authData = UserInfo::getAuthData();
		$fileURL = $item->getRemoteFileURL();
		$fileURL .= "&auth_token={$authData['auth_token']}&auth_uname={$authData['auth_uname']}&auth_timestamp={$authData['auth_timestamp']}";

		return $fileURL;
	}

	public function install_remote($type, $remoteCID=null, $install=false){
		if (empty($remoteCID)) {
			return array(Package::E_PACKAGE_NOT_FOUND);
		}

		$fileURL = $this->get_remote_url($type, $remoteCID);
		if (empty($fileURL)) {
			return array(Package::E_PACKAGE_NOT_FOUND);
		}

		$file = $this->download_remote_package($fileURL);
		if (empty($file) || $file == Package::E_PACKAGE_DOWNLOAD) {
			return array(Package::E_PACKAGE_DOWNLOAD);
		} else if ($file == Package::E_PACKAGE_SAVE) {
			return array($file);
		}

		try {
			Loader::model('package_archive');
			$am = new PackageArchive($item->getHandle());
			$am->install($file, true);
		} catch (Exception $e) {
			return array($e->getMessage());
		}

		if ($install) {
        	$tests = Package::testForInstall($item->getHandle());
        	if (is_array($tests)) {
				return $tests;
        	} else {
            	$p = Loader::package($item->getHandle());
            	try {
                	$p->install();
            	} catch(Exception $e) {
					return array(Package::E_PACKAGE_INSTALL);
            	}
			}
        }

		return true;
	}

	private function download_remote_package($fileURL) {
		$fh = Loader::helper('file');
		$pkg = $fh->getContents($fileURL);
		if (empty($pkg)) {
			return Package::E_PACKAGE_DOWNLOAD;
		}

		$file = time();
		// Use the same method as the Archive library to build a temporary file name.
		$tmpFile = $fh->getTemporaryDirectory() . $file . '.zip';
		$fp = fopen($tmpFile, "wb");
		if ($fp) {
			fwrite($fp, $pkg);
			fclose($fp);
		} else {
			return Package::E_PACKAGE_SAVE;
		}

		return $file;
	}

}
