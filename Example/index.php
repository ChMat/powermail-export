<?php
/*
 * This file is part of the powermail-export package.
 *
 * (c) Christian Mattart <christian@chmat.be>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

require('../parsePowermail.php');

use ChMat\PowermailExport\parsePowermail;

try {
    if (!array_key_exists('file', $_GET))
    {
        throw new \Exception('Please provide a filename under `file` GET parameter.');
    }

    $fileToParse = 'files/' . $_GET['file'];

    if (!@file_exists($fileToParse) or !is_readable($fileToParse))
    {
        throw new \Exception('File was not found or could not be opened in `files/` directory. Please verify it has the adequate permissions.');
    }

    include($fileToParse);
    
    if (!isset($tx_powermail_fields) or !isset($tx_powermail_mails))
    {
        throw new \Exception('File was found but did not contain required $tx_powermail_mails or $tx_powermail_fields variables.');
    }

    // Load the parser
    $parser = new parsePowermail();

    //// Parse the answers and return an array.
    //$answers = $parser->parse($tx_powermail_fields, $tx_powermail_mails);
    
    // Or
    
    // Parse the answers and send a csv file to the user
    $parser->parseAndDownload($tx_powermail_fields, $tx_powermail_mails, 'customFilename.csv');

    print_r($answers);
}
catch (\Exception $e)
{
    echo $e->getMessage();
}
