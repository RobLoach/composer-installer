<?php

/**
 * @file
 * Definition of Drupal\ComposerInstaller\DrupalInstaller.
 */
namespace Drupal\ComposerInstaller;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

/**
 * Adds support to Composer to install Drupal projects.
 *
 * Composer projects must be of type "drupal-module", "drupal-theme" or
 * "drupal-drush" in order to install correctly.
 */
class DrupalInstaller extends LibraryInstaller {

  /**
   * {@inheritDoc}
   */
  public function supports($packageType) {
    // Allow the installation of Drupal modules, themes and Drush components.
    $supported = array(
      'drupal-module',
      'drupal-theme',
      'drupal-drush',
    );
    return in_array($packageType, $supported);
  }

  /**
   * {@inheritDoc}
   */
  public function getInstallPath(PackageInterface $package) {
    switch ($package->getType()) {
      switch 'drupal-drush':
        return $this->serverHome() . '/.drush/' . $package->getName();
        break;
      switch 'drupal-module':
        return $this->locateDrupalRoot() . '/sites/all/modules/' . $package->getName();
        break;
      switch 'drupal-theme':
        return $this->locateDrupalRoot() . '/sites/all/theme/' . $package->getName();
        break;
    }
  }

  /**
   * Exhaustive depth-first search to try and locate the Drupal root directory.
   */
  protected function locateDrupalRoot($start_path = NULL) {
    $drupal_root = FALSE;

    $start_path = empty($start_path) ? getcwd() : $start_path;
    foreach (array(TRUE, FALSE) as $follow_symlinks) {
      $path = $start_path;
      if ($follow_symlinks && is_link($path)) {
        $path = realpath($path);
      }
      // Check the start path.
      if ($this->validDrupalRoot($path)) {
        $drupal_root = $path;
        break;
      }
      else {
        // Move up dir by dir and check each.
        while ($path = dirname($path)) {
          if ($follow_symlinks && is_link($path)) {
            $path = realpath($path);
          }
          if ($this->validDrupalRoot($path)) {
            $drupal_root = $path;
            break 2;
          }
        }
      }
    }

    return $drupal_root;
  }

  /**
   * Checks whether given path qualifies as a Drupal root.
   *
   * @param $path
   *   The relative path to common.inc (varies by Drupal version), or FALSE if
   *   not a Drupal root.
   */
  protected function validDrupalRoot($path) {
    if (!empty($path) && is_dir($path)) {
      $candidates = array('includes/common.inc', 'core/includes/common.inc');
      foreach ($candidates as $candidate) {
        if (file_exists($path . '/' . $candidate)) {
          return $candidate;
        }
      }
    }
    return FALSE;
  }

  /**
   * Return the user's home directory.
   */
  protected function serverHome() {
    $home = getenv('HOME');
    if (empty($home)) {
      if (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
        // home on windows
        $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
      }
    }
    return empty($home) ? NULL : $home;
  }
}
