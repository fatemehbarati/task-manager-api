<?php
namespace Fatemeh\TaskManagerApi\Services;

class TaskValidator {
    public function __construct()
    {
    }

    public function validateInput(?array $input = []) : array {
        $errors = [];
        $input ??= [];

        if(!array_key_exists('title', $input)) {
            $errors[] = "Title is missing.";
        }elseif (!is_string($input['title'])) {
            $errors[] = "Title is the wrong type.";
        }elseif (trim($input['title']) === '') {
            $errors[] = "Title should not be empty.";
        } 

        if(array_key_exists('done', $input)) {
            if(!is_bool($input['done'])) {
                $errors[] = "Done is the wrong type.";
            }
        }
        
        return $errors;
    }
}