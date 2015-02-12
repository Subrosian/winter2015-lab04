<?php

/**
 * Data access wrapper for "orders" table.
 *
 * @author jim
 */
class Orders extends MY_Model {

    // constructor
    function __construct() {
        parent::__construct('orders', 'num');
    }

    // add an item to an order, of quantity $num and code $code
    function add_item($num, $code) {
        $CI = & get_instance();
        if ($CI->orderitems->exists($num, $code)) //if it already exists in the order, then increment the quantity
        {
            $record = $CI->orderitems->get($num, $code);
            $record->quantity++;
            $CI->orderitems->update($record); //update in the DB with new $record data
        } else //create an empty orderitem record, and populate its fields appropriately
        {
            $record = $CI->orderitems->create();
            $record->order = $num;
            $record->item = $code;
            $record->quantity = 1;
            $CI->orderitems->add($record);
        }
    }

    // calculate the total for an order - and return a string value for the total
    function total($num) {
    // the autoloaded orderitems is in the scope of the controller, not the model.
    // This gets access to the orderitems model within orders as well
    $CI = &get_instance();
    $CI->load->model('orderitems');

    //get the items in this order
    $items = $this->orderitems->some('order', $num);
    
    //iterate through items, get total
    $total = 0.00;
    foreach($items as $item) {
        $menuitem = $this->menu->get($item->item); //where item is the key of the $item element?
        $total += $item->quantity * $menuitem->price;
    }
    
    return number_format($total, 2);
    }

    // retrieve the details for an order
    function details($num) {
        
    }

    // cancel an order
    function flush($num) {
        $CI = & get_instance();
        $CI->load->model('orderitems');
        $this->orderitems->delete_some($num); //delete orderitems records that were created in the process of creating this order
        
    }

    // validate an order
    // it must have at least one item from each category
    function validate($num) {
        $CI = & get_instance();
        $items = $CI->orderitems->group($num);
        $gotem = array(); //array to contain m, d, s key-value pairs
        if (count($items) > 0)
            foreach ($items as $item) //essentially check off items' category
            {
                $menu = $CI->menu->get($item->item);
                $gotem[$menu->category] = 1;
            }
        return isset($gotem['m']) && isset($gotem['d']) && isset($gotem['s']); //true if it has one item from each category
    }

}
