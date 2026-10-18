<?php

namespace Tests\Feature;

use Tests\TestCase;

class NotepadTest extends TestCase
{
    public function test_notepad_page_is_displayed(): void
    {
        $this->get('/notepad')->assertOk()->assertSee('Notepad')->assertSee('notepad.v1');
    }
}
