# Powermail Export Example

Here is a quick example of how you can use Powermail Export.
 
You need phpMyAdmin to export powermail data as follows.

## Export Questions

Get all questions for the page you want to export.

    select uid, title, fieldset, sorting from tx_powermail_fields where pid = ? order by fieldset, sorting
    
Export result as php array. It should look like this:
    
    $tx_powermail_fields = array(
        array('uid' => '1899','title' => 'Your first question','fieldset' => '204','sorting' => '1'),
        array('uid' => '1898','title' => 'Another question','fieldset' => '214','sorting' => '1'),
        // ...
    );

## Export Answers

    select uid, crdate, piVars from tx_powermail_mails where pid = ? order by uid

Export result as php array. It should look like this:

    $tx_powermail_mails = array(
        array('uid' => '19718','crdate' => '1441885043','piVars' => '<piVars>
    	<uid1899>A simple answer</uid1899>
    	<uid1859></uid1859>
    	<uid1860 type="array">
    		<numIndex index="0">Selected option</numIndex>
    		<numIndex index="1"></numIndex>
    		<numIndex index="2"></numIndex>
    		<numIndex index="3"></numIndex>
    		<numIndex index="4"></numIndex>
    		<numIndex index="5">Another option</numIndex>
    		<numIndex index="6">And another one</numIndex>
    		<numIndex index="7"></numIndex>
    		<numIndex index="8"></numIndex>
    	</uid1860>
    </piVars>'),
        // ...
    );

## Save both exports in a new file under `files` directory

Copy and paste both exports to a new file `files/myPowermail.php`.

    <?php
    $tx_powermail_mails  = array(/* ... */);
    $tx_powermail_fields = array(/* ... */);


## Run script

Run `index.php?file=myPowermail.php` in your browser and download generated csv file.
