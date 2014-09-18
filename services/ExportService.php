<?php
namespace Craft;

class ExportService extends BaseApplicationComponent 
{

    public $_service;
    public $delimiter = ExportModel::DelimiterComma;

    public function download($settings) 
    {
    
        // Get max power
        craft()->config->maxPowerCaptain();
        
        // Check what service we're gonna need
        $this->_service = 'export_' . strtolower($settings['type']);
        
        // Create the export template
        $export = "";
        
        // Get data
        $data = $this->getData($settings);
        
        // If there is data, process
        if(count($data)) {
        
            // Count rows
            $rows = 0;
            
            // Loop trough data
            foreach($data as $element) {
            
                $row = "";
                
                // Get fields
                $fields = $this->parseFields($settings['map'], $element);
                
                // Put down columns
                if(!$rows) {
                    $row .= $this->parseColumns($settings, $fields);
                }
            
                // Loop trough the fields
                foreach($fields as $handle => $data) {
                    
                    // Parse element data
                    $data = $this->parseElementData($handle, $data);
                    
                    // Parse field data
                    $data = $this->parseFieldData($handle, $data);
                
                    // Put in quotes and escape
                    $row .= '"'.addslashes($data).'"'.$this->delimiter;
                
                }
                
                // Remove last comma
                $row = substr($row, 0, -1);
                
                // Encode row
                $row = StringHelper::convertToUTF8($row);
                
                // And start a new line
                $row = $row . "\r\n";
                
                // Append to data
                $export .= $row;
                
                // Count rows
                $rows++;
            
            }
                
        }
        
        // Return the data to controller
        return $export;
    
    }
    
    protected function getData($settings) 
    {
    
        // Get other sources
        $sources = craft()->plugins->call('registerExportSource', array($settings));
        
        // Loop through sources, see if we can get any data
        $data = array();
        foreach($sources as $plugin) {
            if(is_array($plugin)) {
                foreach($plugin as $source) {
                    $data[] = $source;
                }
            }
        }
                        
        // If no data from source, get data by ourselves
        if(!count($data)) {
        
            // Get service
            $service = $this->_service;
        
            // Find data
            $criteria = craft()->$service->setCriteria($settings);
            
            // Gather data
            $data = $criteria->find();
        
        }
        
        return $data;
    
    }
    
    // Parse fields
    protected function parseFields($map, $element) 
    {
    
        $fields = array();
    
        // Only get element attributes and content attributes
        if($element instanceof BaseElementModel) {
            $attributes = array_merge($element->getAttributes(), $element->getContent()->getAttributes());
        } else {
            $attributes = $element;
        }
        
        // Loop through the map
        foreach($map as $handle => $checked) {
        
            // Only get checked fields
            if($checked == '1' && array_key_exists($handle, $attributes)) {
            
                // Fill them with data
                $fields[$handle] = $attributes[$handle];
            
            }
        
        }
        
        return $fields;
    
    }
    
    // Parse column names
    protected function parseColumns($settings, $fields) 
    {
    
        $columns = "";
        
        // Get service
        $service = $this->_service;
        
        // Loop trough fields
        foreach($fields as $handle => $data) {
        
            // Get field info
            $field = craft()->fields->getFieldByHandle($handle);
            
            // If not a field...
            if(is_null($field)) {
            
                switch($handle) {
                
                    case ExportModel::HandleId:
                        $columns .= '"'.Craft::t("ID").'"'.$this->delimiter;
                        break;
                
                    case ExportModel::HandleStatus:
                        $columns .= '"'.Craft::t("Status").'"'.$this->delimiter;
                        break;
                        
                    default:
                        $columns .= craft()->$service->parseColumn($handle, $settings, $this->delimiter);
                        break;
                
                }
            
            } else {
            
                $columns .= '"'.addslashes($field->name).'"'.$this->delimiter;
            
            }
                    
        }
                
        // Remove last comma
        $columns = substr($columns, 0, -1);
        
        // Encode columns
        $columns = StringHelper::convertToUTF8($columns);
        
        // And start a new line
        $columns = $columns . "\r\n";
        
        return $columns;
    
    }

    // Parse reserved element values
    protected function parseElementData($handle, $data) 
    {
    
        switch($handle) {
        
            case ExportModel::HandleAuthor:
                
                // Get username of author
                $data = craft()->users->getUserById($data)->username;
                                
                break;
                
            case ExportModel::HandleEnabled:
            
                // Make data human readable
                switch($data) {
                
                    case "0":
                        $data = Craft::t("No");
                        break;
                
                    case "1":
                        $data = Craft::t("Yes");
                        break;
                        
                }
            
                break;
        
        }
        
        return $data;
        
    }
    
    // Parse field values
    protected function parseFieldData($handle, $data) 
    {
    
        // Do we have any data at all
        if(!is_null($data)) {
               
            // Get field info
            $field = craft()->fields->getFieldByHandle($handle);
           
            // If it's a field ofcourse
            if(!is_null($field)) {
               
                // For some fieldtypes the're special rules
                switch($field->type) {
               
                    case ExportModel::FieldTypeEntries:
                    case ExportModel::FieldTypeCategories:
                   
                        // Show title
                        $data = $data->first()->title;
                                           
                        break;
                        
                    case ExportModel::FieldTypeAssets:
                    
                        // Show filename
                        $data = $data->first()->filename;
                        
                        break;
                   
                    case ExportModel::FieldTypeUsers:
                   
                        // Show username
                        $data = $data->first()->username;
                                           
                        break;
                        
                    case ExportModel::FieldTypeLightswitch:
                    
                        // Make data human readable
                        switch($data) {
                        
                            case "0":
                                $data = Craft::t("No");
                                break;
                        
                            case "1":
                                $data = Craft::t("Yes");
                                break;
                        
                        }
                        
                        break;
               
                }
           
            }
            
        } else {
        
            // Don't return null, return empty
            $data = "";
        
        }
                               
        return $data;
   
    }

}