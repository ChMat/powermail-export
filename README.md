# Powermail Export

This script is a quick way of exporting Powermail answer data into a clean array or csv file.

[Powermail Typo3 extension][1] did not export well int. Questions for which a user did not provide
an answer are not exported. Not even as an empty cell. This results in a csv file that is a nightmare
to read. 
 
## Usage

Load class with your favourite autoloader or simply `include()` it.

**Note to Microsoft Excel users**: 

By default, downloaded file is UTF-8 encoded. Should you want to open the file with Microsoft Excel,
then do not forget to convert file contents to ANSI.

### Load Answers into array

    <?php
    
    use ChMat\PowermailExport\ParsePowermail;
    
    $parser = new ParsePowermail();

    $answers = $parser->parse($tx_powermail_fields, $tx_powermail_mails);

    print_r($answers);

### Download Csv

    <?php
    
    use ChMat\PowermailExport\ParsePowermail;
    
    $parser = new ParsePowermail();

    $parser->parseAndDownload($tx_powermail_fields, $tx_powermail_mails, 'customFilename.csv');


## Contribute

Pull requests are welcome on: https://github.com/ChMat/powermail-export

## License

This project is available under the [MIT license][1].
 
[1]: http://typo3.org/extensions/repository/view/powermail
[2]: LICENSE.md
