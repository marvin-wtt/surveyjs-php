<?php


class MySQLDBAdapter extends DatabaseAdapter
{
    public function getDriver()
    {
        return "mysql";
    }

    public function createTables()
    {
        $result0 = $this->dbh->query("CREATE TABLE `surveys` ( "
            ."`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT , "
            ."`uuid` VARCHAR(64) NOT NULL DEFAULT (UUID()) UNIQUE, "
            ."`name` TEXT NOT NULL , "
            ."`json` JSON NOT NULL , "
            ."PRIMARY KEY (`id`)"
            .")");
        $result1 = $this->dbh->query("CREATE TABLE `results` ( "
            ."`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT , "
            ."`uuid` VARCHAR(64) NOT NULL DEFAULT (UUID()) UNIQUE , "
            ."`survey_id` BIGINT UNSIGNED NOT NULL , "
            ."`json` JSON NOT NULL , "
            ."`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , "
            ."PRIMARY KEY (`id`) , "
            ."FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`)"
            .")");
        return $result0 && $result1;
    }

    public function getSurveys()
    {
        $sqlQuery = 'SELECT `uuid` AS `id`, `json`, `name` FROM surveys';
        $stmt = $this->dbh->query($sqlQuery);
        $data = array();
        $result = $stmt->fetchAll();
        foreach($result as $row) {
            $data[$row['id']] = $row;
        }
        return $data;
    }

    public function getSurvey($id)
    {
        $stmt = $this->dbh->prepare("SELECT `json` FROM surveys WHERE `uuid`=:id");
        $stmt->execute([":id" => $id]);
        return $stmt->fetchColumn();
    }

    public function addSurvey($name)
    {
        $stmt = $this->dbh->prepare("INSERT INTO surveys (`name`, `json`) VALUES (:survey_name, :json) ");
        $stmt->execute([":json" => "{}", ":survey_name" => $name]);
        $id = $this->dbh->lastInsertId();
        
        $stmt = $this->dbh->query("SELECT `uuid` FROM `surveys` WHERE `id`=".$id);
        return $stmt->fetchColumn();
    }

    public function storeSurvey($id, $json)
    {
        $stmt = $this->dbh->prepare("UPDATE surveys SET `json`=:json WHERE `uuid`=:id");
        $stmt->execute([":json" => $json, ":id" => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteSurvey($id)
    {
        $stmt = $this->dbh->prepare("DELETE FROM surveys WHERE `uuid`=:id");
        $stmt->execute([":id" => $id]);
    }

    public function changeName($id, $name)
    {
        $stmt = $this->dbh->prepare("UPDATE surveys SET `name`=:survey_name WHERE `uuid`=:id");
        $stmt->execute([":survey_name" => $name, ":id" => $id]);
    }
    
    private function getSurveyIdQuery()
    {
        return "SELECT id FROM `surveys` WHERE `uuid`=:survey_id";
    }
    
    public function postResults($survey_id, $resultsJson)
    {
        $stmt = $this->dbh->prepare("INSERT INTO results (`survey_id`, `json`) VALUES ((".$this->getSurveyIdQuery()."), :json)");
        $success = $stmt->execute([":survey_id" => $survey_id, ":json" => $resultsJson]);
        
        $id = $this->dbh->lastInsertId('results');
        $stmt = $this->dbh->query("SELECT `uuid` FROM `results` WHERE `id`=".$id);
        return $stmt->fetchColumn();
    }

    public function getResults($survey_id)
    {
        $stmt = $this->dbh->prepare("SELECT `json` FROM results WHERE `survey_id`=(".$this->getSurveyIdQuery().")");
        $stmt->execute([':survey_id' => $survey_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}