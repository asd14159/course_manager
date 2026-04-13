<?php

class Controller_Home extends Controller_Base
{
    public function action_index($course_id = null)
    {
        $data = array();

        $data['courses'] = \Model_Course::get_all_by_user($this->user_id) ?: [];

        if ($course_id) 
        {
            $data['assignments'] = \Model_Assignment::get_by_course($course_id);
            
            $data['selected_course'] = \Model_Course::find($course_id);
        } 
        else 
        {
            $data['assignments'] = \Model_Assignment::get_all_by_user($this->user_id);
        }

        $this->template->title = 'ダッシュボード';

        $this->template->content = \View::forge('home/index', $data);
    }
}