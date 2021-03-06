<?php
/**
 * \Elabftw\Elabftw\Upload
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \Exception;
use \Elabftw\Elabftw\Tools as Tools;

/**
 * What happens when you upload a file. This class has two public methods : uploadFile() for normal upload
 * and uploadLocalFile() for calling it from the ImportZip class.
 *
 *   we receive the file in $file. The array looks like that :
 *   name : filename.pdf
 *   type : "application/pdf"
 *   tmp_name "/tmp/phpLzaurte"
 *   error : 0
 *   size 134482
 */
class Upload extends Make
{
    /** the id of the item we upload to */
    protected $itemId;

    /** the type of item */
    protected $itemType;

    /** what algo for hashing */
    protected $hashAlgorithm = 'sha256';

    /** our favorite pdo object */
    protected $pdo;

    /**
     * Create the pdo object and check itemType and id
     * @param string $itemType Type of item (experiments or items)
     * @param int $itemId Id of our item
     *
     */
    public function __construct($itemType, $itemId)
    {
        $this->pdo = Db::getConnection();

        $this->itemType = $this->checkType($itemType);
        $this->checkItemId($itemId);
    }

    /**
     * Main method for normal file upload
     *
     * @param string|array $file Either pass it the $_FILES array or the string of the local file path
     *
     */
    public function uploadFile($file)
    {
        if (!is_array($file) || count($file) === 0) {
            throw new Exception('No files received');
        }
        // check we own the experiment we upload to
        $this->checkPermission();

        $realName = $this->getSanitizedName($file['file']['name']);
        $longName = $this->getCleanName() . "." . Tools::getExt($realName);
        $fullPath = ELAB_ROOT . 'uploads/' . $longName;

        // Try to move the file to its final place
        $this->moveFile($file['file']['tmp_name'], $fullPath);

        // final sql
        $this->dbInsert($realName, $longName, $this->getHash($fullPath));
    }

    /**
     * Called from ImportZip class
     *
     * @param string $file The string of the local file path stored in .elabftw.json of the zip archive
     */
    public function uploadLocalFile($file)
    {
        if (!is_readable($file)) {
            throw new Exception('No file here!');
        }

        $realName = basename($file);
        $longName = $this->getCleanName() . "." . Tools::getExt($realName);
        $fullPath = ELAB_ROOT . 'uploads/' . $longName;

        $this->moveFile($file, $fullPath);

        $this->dbInsert($realName, $longName, $this->getHash($fullPath));
    }

    /**
     * Verify the itemId received
     *
     * @param int itemId Id of our item
     * @throws Exception if id is not pos int
     */
    private function checkItemId($itemId)
    {
        if (is_pos_int($itemId)) {
            $this->itemId = $itemId;
        } else {
            throw new Exception('Bad item id');
        }
    }

    /**
     * Can we upload to that experiment?
     * Make sure we own it.
     *
     * @throws Exception if we cannot upload file to this experiment
     */
    private function checkPermission()
    {
        if ($this->itemType === 'experiments') {
            if (!is_owned_by_user($this->itemId, 'experiments', $_SESSION['userid'])) {
                throw new Exception('Not your experiment!');
            }
        }
    }

    /**
     * Create a clean filename
     * Remplace all non letters/numbers by '.' (this way we don't lose the file extension)
     *
     * @param string $rawName The name of the file as it was on the user's computer
     * @return string The cleaned filename
     */
    private function getSanitizedName($rawName)
    {
        return preg_replace('/[^A-Za-z0-9]/', '.', $rawName);
    }

    /**
     * Place a file somewhere
     *
     * @param $string orig from
     * @param string dest to
     * @throws Exception if cannot move the file
     */
    private function moveFile($orig, $dest)
    {
        if (!rename($orig, $dest)) {
            throw new Exception('Error while moving the file. Check folder permissons!');
        }
    }

    /**
     * Generate the hash based on selected algorithm
     *
     * @param string $file The full path to the file
     * @return string|null the hash or null if file is too big
     */
    private function getHash($file)
    {
        if (filesize($file) < 5000000) {
            return hash_file($this->hashAlgorithm, $file);
        }

        return null;
    }

    /**
     * Create a unique long filename
     *
     * @return string Return a random string
     */
    protected function getCleanName()
    {
        return hash("sha512", uniqid(rand(), true));
    }

    /**
     * Make the final SQL request to store the file
     *
     * @param string $realName The clean name of the file
     * @param string $longName The sha512 name
     * @param string $hash The hash string of our file
     * @throws Exception if request fail
     */
    private function dbInsert($realName, $longName, $hash)
    {
        $sql = "INSERT INTO uploads(
            real_name,
            long_name,
            comment,
            item_id,
            userid,
            type,
            hash,
            hash_algorithm
        ) VALUES(
            :real_name,
            :long_name,
            :comment,
            :item_id,
            :userid,
            :type,
            :hash,
            :hash_algorithm
        )";

        $req = $this->pdo->prepare($sql);

        if (!$req->execute(array(
            'real_name' => $realName,
            'long_name' => $longName,
            // comment can be edited after upload
            // not i18n friendly because it is used somewhere else (not a valid reason, but for the moment that will do)
            'comment' => 'Click to add a comment',
            'item_id' => $this->itemId,
            'userid' => $_SESSION['userid'],
            'type' => $this->itemType,
            'hash' => $hash,
            'hash_algorithm' => $this->hashAlgorithm
        ))) {
            throw new Exception('Cannot add to SQL database!');
        }
    }
}
