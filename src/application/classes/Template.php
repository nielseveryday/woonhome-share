<?php

namespace Template;

class Template {

    /**
     * @param $file_name
     * @return false|string
     */
    function getTemplateContent($file_name){
        if (file_exists(__DIR__ . '/../../files/' . $file_name)) {
            $content = file_get_contents(__DIR__ . '/../../files/' . $file_name);
        } else {
            $content = "Tried to open the file: \"$file_name\", but I can't find it!";
        }
        return $content;
    }

    /**
     *
     * @param $template_content
     * @param $placeholder_data
     * @return array|mixed|string|string[]
     */
    function parseTemplate($template_content, $placeholder_data){
        $template_content = json_decode($template_content);

        foreach($template_content as $part => $data) {
            if ($part == 'product' && isset($data[0])) {
                foreach($data[0] as $key => $value) {
                    //var_dump($key, $value);
                    if (is_string($value) || is_float($value) || is_numeric($value)) {
                        if ($key == 'price' || $key == 'discount') {
                            $value = number_format(($value/100), 2, ',', '.');
                        }
                        $placeholder_data = str_replace("##$key##", $value, $placeholder_data);
                    }
                    if ($key == "images" && is_array($value)) {
                        $placeholder_data = str_replace("##$key##", $value[0], $placeholder_data);
                    }
                }
                //break;
            }
            if ($part == 'profile') {
                foreach(reset($data) as $key => $value) {
                    if (is_string($value) || is_float($value) || is_numeric($value)) {
                        $placeholder_data = str_replace("##$key##", $value, $placeholder_data);
                    }
                }
            }
        }
        return $placeholder_data;
    }
}
