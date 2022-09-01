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
     * Parse the data to a template
     *
     * @param $type
     * @param $template_content
     * @param $placeholder_data
     *
     * @return array|mixed|string|string[]
     */
    function parseTemplate($type, $template_content, $placeholder_data)
    {
        $template_content = json_decode($template_content);

        if ($type == 'sale' || $type == 'product') {
            $placeholder_data = $this->parseProduct($template_content, $placeholder_data);
        } else {
            $placeholder_data = $this->parseArticle($template_content, $placeholder_data);
        }

        //Some other values to change
        $placeholder_data = str_replace("##year##", date('Y'), $placeholder_data);

        //Delete not filled out template parts
        $placeholder_data = $this->replaceEmptyParts($placeholder_data, '##', '##', '-');

        return $placeholder_data;
    }

    /**
     * Parse a sale or product template
     *
     * @param $template_content
     * @param $placeholder_data
     *
     * @return string
     */
    private function parseProduct($template_content, $placeholder_data)
    {
        $price = 0;
        $discount = 0;
        foreach($template_content as $part => $data) {
            if ($part == 'product' && isset($data[0])) {
                $colors = array();
                $materials = array();
                foreach($data[0] as $key => $value) {
                    if (is_string($value) || is_float($value) || is_numeric($value)) {
                        if ($key == 'price' || $key == 'discount') {
                            $value = number_format(($value/100), 2, ',', '.');
                        }
                        if ($key == 'price') {
                            $price = $value;
                        }
                        if ($key == 'discount') {
                            $discount = $value;
                            if ($discount !== $price) {
                                $value = '<span class="from" itemprop="price" >€ '.$price.'</span> <span class="green">€ '.$discount.'</span>';
                            } else {
                                $value = '<span class="green" itemprop-="price">€ '.$price.'</span>';
                            }
                        }
                        if (str_starts_with($key,'c_') && $value == 1) {
                            $colors[] = ucfirst(str_replace('c_', '', $key));
                        }
                        if (str_starts_with($key,'m_') && $value == 1) {
                            $materials[] = ucfirst(str_replace('m_', '', $key));
                        }
                        $placeholder_data = str_replace("##$key##", $value, $placeholder_data);
                    }
                    if ($key == "images" && is_array($value)) {
                        $placeholder_data = str_replace("##$key##", $value[0], $placeholder_data);
                    }
                }
                $placeholder_data = str_replace("##kleuren##", implode(', ', $colors), $placeholder_data);
                $placeholder_data = str_replace("##materialen##", implode(', ', $materials), $placeholder_data);
            }
            if ($part == 'profile') {
                foreach(reset($data) as $key => $value) {
                    if (is_string($value) || is_float($value) || is_numeric($value)) {
                        $placeholder_data = str_replace("##$key##", $value, $placeholder_data);
                    }
                }
            }
            if ($part == 'breadcrumbs') {
                $breadcrumbs = '';
                foreach($data as $key => $value) {
                    $breadcrumbs.= sprintf(
                        "/ <a href=%s>%s</a>",
                        'https://woonhome.nl/' . str_replace('/', '', $value->slug),
                        $value->name
                    );
                }
                $placeholder_data = str_replace("##breadcrumbs##", $breadcrumbs, $placeholder_data);
            }
        }

        return $placeholder_data;
    }

    /**
     * Parse a article template
     *
     * @param $template_content
     * @param $placeholder_data
     *
     * @return string
     */
    private function parseArticle($template_content, $placeholder_data)
    {
        foreach($template_content as $part => $data) {
            if ($part == 'items' && isset($data[0])) {
                foreach ($data[0] as $key => $value) {
                    if ($key == 'post_content') {
                        $value = str_replace('alt="',  'itemprop="image" alt="', $value);
                    }
                    if (is_string($value) || is_float($value) || is_numeric($value)) {
                        $placeholder_data = str_replace("##$key##", $value, $placeholder_data);
                    }
                }
            }
        }

        return $placeholder_data;
    }

    /**
     * Replace empty template parts
     *
     * @param $str
     * @param $start
     * @param $end
     * @param $replacement
     *
     * @return string
     */
    private function replaceEmptyParts($str, $start = '##', $end = '##', $replacement = '') {
        $start = preg_quote($start, '/');
        $end = preg_quote($end, '/');
        $regex = "/({$start})(.*?)({$end})/";
        return preg_replace($regex, $replacement, $str);
    }
}
