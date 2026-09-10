<?php
namespace Fatemeh\TaskManagerApi\tests;

use Fatemeh\TaskManagerApi\Services\TaskValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskValidator::class)]
class TaskValidatorTest extends TestCase {
    public TaskValidator $taskValidator;

    public function setUp() : void {
        $this->taskValidator = new TaskValidator();
    }

    public function testValidDataPassesValidation() : void {
        $result = $this->taskValidator->validateInput(["title" => "Fatemeh"]);
        $this->assertEmpty($result);
    }

    public function testValidDataPassesWithDoneValidation() : void {
        $result = $this->taskValidator->validateInput(["title" => "Fatemeh", 'done' => true]);
        $this->assertEmpty($result);
    }
    
    public function testMultibyteTitleUnderCharLimitPasses(): void {
        // 200 Persian chars ≈ 400 bytes, but well under the 255-CHARACTER limit
        $result = $this->taskValidator->validateInput(['title' => str_repeat('س', 200)]);
        $this->assertEmpty($result);
    }
    
    public function testNullInputReturnsError(): void {
        $result = $this->taskValidator->validateInput(null);
        $this->assertNotEmpty($result);
        $this->assertEquals(['Title is missing.'], $result);
    }

    public function testNoTitleReturnsError() : void {
        $result = $this->taskValidator->validateInput([]);
        $this->assertNotEmpty($result);
        $this->assertArraysAreEqual(['Title is missing.'], $result);
    }

    public function testEmptyTitleReturnsError(): void {
        $result = $this->taskValidator->validateInput(["title" => '']);
        $this->assertNotEmpty($result);
        $this->assertArraysAreEqual(['Title should not be empty.'], $result);
    }

    public function testNonStringTitleReturnsError(): void {
        $result = $this->taskValidator->validateInput(['title' => 123]);
        $this->assertNotEmpty($result);
        $this->assertArraysAreEqual(['Title is the wrong type.'], $result);
    }

    public function testTitleExceededLengthReturnsError(): void {
        $result = $this->taskValidator->validateInput(["title" => str_repeat('test', 256)]);
        $this->assertNotEmpty($result);
        $this->assertArraysAreEqual(["Title's length should be less than 255 characters."], $result);
    }

    public function testNonBoolDoneReturnsError(): void {
        $result = $this->taskValidator->validateInput(["title" => "Test", 'done' => 'test']);
        $this->assertNotEmpty($result);
        $this->assertArraysAreEqual(["Done is the wrong type."], $result);
    }
    
    public function testMultipleErrorsAccumulate(): void {
        $result = $this->taskValidator->validateInput(['title' => '', 'done' => 'not a bool']);
        $this->assertCount(2, $result);
        $this->assertArraysAreEqual(["Title should not be empty.", "Done is the wrong type."], $result);
    }
}