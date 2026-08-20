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
        }elseif(mb_strlen($input['title']) > 255) {
            $errors[] = "Title's length should be less than 255 characters.";
        }

        if(array_key_exists('done', $input)) {
            if(!is_bool($input['done'])) {
                $errors[] = "Done is the wrong type.";
            }
        }
        
        return $errors;
    }
}