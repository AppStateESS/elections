<?php

namespace election\Factory;

require_once PHPWS_SOURCE_DIR . 'mod/election/vendor/autoload.php';

use election\Resource\Student;
use Guzzle\Http\Client;

/**
 * BannerStudentProvider
 *
 * Returns a Student object with data pulled from a web service connected to Banner.
 *
 * @author Jeremy Booker
 * @package election
 */
class BannerStudentProvider extends StudentProvider {

    private $client;

    // Student level: grad, undergrad
    const UNDERGRAD = 'U';
    const GRADUATE  = 'G';
    const GRADUATE2 = 'G2';
    const DOCTORAL  = 'D';
    const POSTDOC   = 'P'; // Guessing at the name here, not sure what 'P' really is

    public function __construct()
    {
        // Get the REST API URL from the module's settings
        $apiUrl = \PHPWS_Settings::get('election', 'studentDataApiUrl');

        if(is_null($apiUrl)){
            throw new \InvalidArgumentException('Student data API url is not configured.');
        }

        // If the URL doesn't end with a trailing slash, then add one
        if(substr($apiUrl, -1) != '/'){
            $apiUrl .= '/';
        }

        // Create a Guzzle instance
        $this->client = new Client($apiUrl);
    }

    /**
     * Returns a Student object with hard-coded data
     * @return Student
     */
    public function getStudent($studentId)
    {
        if($studentId === null || $studentId == ''){
            throw new \InvalidArgumentException('Missing student ID.');
        }

        $json = $this->sendRequest($studentId);
        var_dump($json);

        // Check for error response like ['Message'] = 'An error has occurred.';
        // TODO

        // Log the request
        $this->logRequest('getStudent', 'success', array($studentId));

        // Create the Student object and plugin the values
        $student = new \election\Resource\Student();
        $this->plugValues($student, $json);

        return $student;
    }

    protected function sendRequest($studentId)
    {
        $request = $this->client->get($studentId); //NB: URL is relative to the base URL from the module's settings

        $response = $request->send();

        return $response->json();
    }

    /**
     * Takes a reference to a Student object and a SOAP response,
     * Plugs the SOAP values into Student object.
     *
     * @param Student $student
     * @param Array $data
     */
    protected function plugValues(&$student, Array $data)
    {
        /**********************
         * Basic Demographics *
         **********************/
        $student->setBannerId($data['ID']);
        $student->setUsername($data['userName']);

        $student->setFirstName($data['firstName']);
        $student->setLastName($data['lastName']);

        /*****************
         * Academic Info *
         *****************/

        // Level (grad vs undergrad)
        if($data['studentLevel'] == self::UNDERGRAD) {
            $student->setLevel(Student::UNDERGRAD);
        } else if ($data['studentLevel'] == self::GRADUATE) {
            $student->setLevel(Student::GRADUATE);
        } else if ($data['studentLevel'] == self::GRADUATE2) {
            $student->setLevel(Student::GRADUATE2);
        } else if ($data['studentLevel'] == self::DOCTORAL) {
            $student->setLevel(Student::DOCTORAL);
        } else if ($data['studentLevel'] == self::POSTDOC) {
            $student->setLevel(Student::POSTDOC);
        } else {
            throw new \InvalidArgumentException("Unrecognized student level ({$data['studentLevel']}) for {$data->banner_id}.");
        }

        // Credit Hours
        $student->setCreditHours($data['creditHoursEnrolled']);

        // Type
        $student->setStudentType($data['studentType']);

        // Classification
        //TODO Check the API's actual format and possible values for this field
        $student->setClass($data['classification']);

        // College
        $student->setCollegeCode($data['collegeCode']);
        $student->setCollegeDesc($data['collegeDesc']);
    }

    /**
     * Logs this request to PHPWS' soap.log file
     */
    private function logRequest($functionName, $result, Array $params)
    {
        $args = implode(', ', $params);
        $msg = "$functionName($args) result: $result";
        \PHPWS_Core::log($msg, 'soap.log', 'SOAP');
    }
}
