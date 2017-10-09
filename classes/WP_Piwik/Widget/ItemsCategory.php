<?php

namespace WP_Piwik\Widget;

class ItemsCategory extends \WP_Piwik\Widget {

    public $className = __CLASS__;

    protected function configure($prefix = '', $params = array()) {
        $timeSettings = $this->getTimeSettings();
        $this->title = $prefix.__('E-Commerce Item Categories', 'wp-piwik');
        $this->method = 'Goals.getItemsCategory';
        $this->parameter = array(
            'idSite' => self::$wpPiwik->getPiwikSiteId($this->blogId),
            'period' => $timeSettings['period'],
            'date'  => $timeSettings['date']
        );
    }

    public function show() {
        $response = self::$wpPiwik->request($this->apiID[$this->method]);
        if (!empty($response['result']) && $response['result'] ='error')
            echo '<strong>'.__('Piwik error', 'wp-piwik').':</strong> '.htmlentities($response['message'], ENT_QUOTES, 'utf-8');
        else {
            $tableHead = array(
                __('Label', 'wp-piwik'),
                __('Revenue', 'wp-piwik'),
                __('Quantity', 'wp-piwik'),
                __('Orders', 'wp-piwik'),
                __('Avg. price', 'wp-piwik'),
                __('Avg. quantity', 'wp-piwik'),
                __('Conversion rate', 'wp-piwik'),
            );
            $tableBody = array();
            if (is_array($response))
                foreach ($response as $data) {
                    array_push($tableBody, array(
                        $data['label'],
                        isset($data['revenue'])?number_format($data['revenue'],2):"-.--",
                        isset($data['quantity'])?$data['quantity']:'-',
                        isset($data['orders'])?$data['orders']:'-',
                        number_format($data['avg_price'],2),
                        $data['avg_quantity'],
                        $data['conversion_rate']
                    ));
                }
            $tableFoot = array();
            $this->table($tableHead, $tableBody, $tableFoot);
        }
    }

}