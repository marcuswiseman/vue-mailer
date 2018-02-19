<?php

    class TemplateEditor {

        public $fields = [];
        public $body = "";

        public function __construct () {

        }

        public static function construct_attrs($field) {
            $result = '';
            foreach($field as $v=>$f) {
                if ($v != 'tag' && $v != 'text') {
                    $result .= " {$v}='{$f}'";
                }
            }
            return $result;
        }

        public static function construct_tag($field) {
            $attrs = self::construct_attrs($field);
            $text = $field['text'];
            return "<{$field['tag']} {$attrs}>{$text}</{$field['tag']}>";
        }   
        
        public function field ($data) {
            $new_field = json_encode($data);
            $this->fields[] = $new_field;
            return $new_field;
        }

        public function field_insert($html) {
            foreach ($this->fields as $v=>$f) {
                $f = (array) json_decode($f);
                $html = str_replace(
                    "<#{$v}>", 
                    self::construct_tag($f), 
                    $html
                );
            }
            $this->body = $html;
            return $html;
        }

    }

?>