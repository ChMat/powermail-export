<?php

/*
 * This file is part of the powermail-export package.
 *
 * (c) Christian Mattart <christian@chmat.be>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace ChMat\PowermailExport;

class parsePowermail
{

    /**
     * Parse Powermail questions and answers and returns array.
     *
     * @param array $tx_powermail_fields Powermail questions
     * @param array $tx_powermail_mails Corresponding Powermail answers
     *
     * @return array
     * @throws \Exception
     */
    public function parse($tx_powermail_fields, $tx_powermail_mails)
    {
        if (!is_array($tx_powermail_mails))
        {
            throw new \Exception('$tx_powermail_mails should be an array.');
        }
        if (!is_array($tx_powermail_fields))
        {
            throw new \Exception('$tx_powermail_fields should be an array.');
        }
        
        $parsedAnswers = $this->parsePowermailAnswers($tx_powermail_mails);
        $questions     = $this->remapQuestions($tx_powermail_fields);
        $questions     = $this->setArrayQuestions($questions, $parsedAnswers);

        // $displayableArray will be used to produce csv file
        $displayableArray = array();

        // Inject question titles in first row and prepare $emptyRow to prepopulate answer rows.
        list($displayableArray, $emptyRow) = $this->injectQuestionTitles($questions, $displayableArray);

        // Inject answers in $displayableArray array and return array to the user.
        return $displayableArray = $this->injectAnswers($parsedAnswers, $displayableArray, $emptyRow);
    }

    /**
     * Parse Powermail questions and answers and sends a csv file to the user.
     *
     * @param array $tx_powermail_fields Powermail questions
     * @param array $tx_powermail_mails Corresponding Powermail answers
     * @param string $downloadAsFilename Filename for download
     */
    public function parseAndDownload($tx_powermail_fields, $tx_powermail_mails, $downloadAsFilename = 'filename.csv')
    {
        $displayableArray = $this->parse($tx_powermail_fields, $tx_powermail_mails);

        $this->sendCsv($displayableArray, $downloadAsFilename);
    }

    /**
     * Analyse all answers provided in $tx_powermail_mails array.
     *
     * Each row of $tx_powermail_mails corresponds to all answers submitted by one user.
     *
     * @param array $tx_powermail_mails
     *
     * @return array
     */
    private function parsePowermailAnswers($tx_powermail_mails)
    {
        $parsedData = array();

        foreach ($tx_powermail_mails as $row)
        {
            $parsedData[$row['uid']] = $this->parsePowermailAnswer($row['piVars']);
        }

        return $parsedData;
    }

    /**
     * Analyse data contained in piVars array item from $tx_powermail_mail.
     *
     * piVars contains all answers submitted by one user.
     *
     * @param array $tx_powermail_mail
     *
     * @return array
     */
    private function parsePowermailAnswer($tx_powermail_mail)
    {
        // Extract all answers. Note some can be spread on several lines (parameter s).
        $matched = preg_match_all('#<uid(\d+)(\stype="array")?>(.*)</uid\\1>#s', $tx_powermail_mail, $matches);

        if ($matched === false)
        {
            return array();
        }

        $parsedData = array();

        /*
         * matches[1] Question uid
         * matches[2] Question type (will be ` type="array"`)
         * matches[3] Actual answer or array for multiple choice questions
         */
        foreach ($matches[1] as $index => $varUid)
        {
            if ($matches[2][$index] == ' type="array"')
            {
                // Extracting multiple choice question answers 
                $parsedData[$varUid] = $this->parsePowermailArrayAnswer($matches[3][$index]);
                continue;
            }

            // Single answer
            $parsedData[$varUid] = $matches[3][$index];
        }

        return $parsedData;
    }

    /**
     * Analyse data contained in multiple choice answer.
     *
     * Array contains all possible items. Items not selected by the user are empty.
     * Selected items contain option text.
     *
     * @param array $rawData
     *
     * @return array
     */
    private function parsePowermailArrayAnswer($rawData)
    {
        // Extract all possible options.
        // IMPORTANT : we consider each option is contained on a single line.
        $matched = preg_match_all('#<numIndex index="(\d+)">(.*)</numIndex>#u', $rawData, $matches);

        if ($matched === false)
        {
            return array();
        }

        $parsedData = array();

        /*
         * matches[1] Option index (0-indexed)
         * matches[2] Option text, if it was selected by the user (otherwise, empty)
         */
        foreach ($matches[1] as $index => $varUid)
        {
            $parsedData[$varUid] = $matches[2][$index];
        }

        return $parsedData;
    }

    /**
     * Reindex questions array on question uid.
     *
     * @param array $questions
     *
     * @return array
     */
    private function remapQuestions($questions)
    {
        $new = array();
        foreach ($questions as $id => $question)
        {
            $new[$question['uid']] = array(
                'title' => $question['title'],
                'array' => false, // Will be set to true if question is a multiple choice.
            );
        }

        return $new;
    }

    /**
     * Browse all $answers in order to fetch all possible options to multiple choice questions.
     * Inject the options in $questions array.
     *
     * @param $questions
     * @param $answers
     *
     * @return mixed
     */
    private function setArrayQuestions($questions, $answers)
    {
        foreach ($answers as $items)
        {
            foreach ($items as $uid => $item)
            {
                // Answer is an array
                if (is_array($item))
                {
                    if (!array_key_exists('options', $questions[$uid]))
                    {
                        $questions[$uid]['array']   = true; // Mark question as multiple choice.
                        $questions[$uid]['options'] = array(); // Placeholder for question options.
                    }
                    foreach ($item as $index => $option)
                    {
                        // Skip empty options
                        if (empty($option) and array_key_exists($index, $questions[$uid]['options']))
                        {
                            continue;
                        }
                        $questions[$uid]['options'][$index] = empty($option) ? '[option]' : $option;
                    }

                    ksort($questions[$uid]['options']);
                }
            }
        }

        return $questions;
    }

    /**
     * Loop on $questions in order to:
     * - inject question titles as first row in $displayableArray
     * - prepare $emptyRow to inject as sample row before setting real answers.
     *
     * @param array $questions
     * @param array $displayableArray
     *
     * @return array
     */
    private function injectQuestionTitles($questions, $displayableArray)
    {
        $emptyRow = array();

        foreach ($questions as $uid => $question)
        {
            if ($question['array'])
            {
                // Each option has its own column.
                foreach ($question['options'] as $index => $option)
                {
                    // Question title will look like "Question [optionx]".
                    $questionTitle                                          = sprintf('%s [%s]', $question['title'], $option);
                    $displayableArray[0]['question_' . $uid . '_' . $index] = $questionTitle;
                    $emptyRow['question_' . $uid . '_' . $index]            = '';
                }
            }
            else
            {
                // Simple question
                $displayableArray[0]['question_' . $uid] = $question['title'];
                $emptyRow['question_' . $uid]            = '';
            }

        }

        return array($displayableArray, $emptyRow);
    }

    /**
     * Loop on $parsedAnswers and inject them into $displayableArray.
     *
     * @param array $parsedAnswers
     * @param array $displayableArray
     * @param array $emptyRow
     *
     * @return array
     */
    private function injectAnswers($parsedAnswers, $displayableArray, $emptyRow)
    {
        // Loop on answers
        foreach ($parsedAnswers as $row => $questions)
        {
            // Inject a sample row to ensure all fields are present in export
            $displayableArray[$row] = $emptyRow;

            // Populate answers on current row
            foreach ($questions as $uid => $answer)
            {
                if (is_array($answer))
                {
                    foreach ($answer as $index => $option)
                    {
                        $displayableArray[$row]['question_' . $uid . '_' . $index] = $option;
                    }
                }
                else
                {
                    $displayableArray[$row]['question_' . $uid] = $answer;
                }
            }

        }

        return $displayableArray;
    }

    /**
     * Génère un fichier csv et l'envoie à l'utilisateur.
     *
     * @param array $data
     * @param string $filename
     */
    private function sendCsv($data, $filename)
    {
        $csvContent = '';

        foreach ($data as $row)
        {
            $item = 0;
            foreach ($row as $cell)
            {
                $csvContent .= $item > 0 ? ';"' . $cell . '"' : '"' . $cell . '"';
                $item ++;
            }
            $csvContent .= "\r\n";
        }

        header(sprintf('Content-Disposition: attachment; filename="%s";', $filename));
        header('Content-Type: application/csv');

        echo $csvContent;

        exit;
    }
}



