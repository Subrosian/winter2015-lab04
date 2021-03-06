<?php

/**
 * Order handler
 * 
 * Implement the different order handling usecases.
 * 
 * controllers/welcome.php
 *
 * ------------------------------------------------------------------------
 */
class Order extends Application {

    function __construct() {
        parent::__construct();
    }

    // start a new order
    function neworder() {
        $order_num = $this->orders->highest() + 1;
        
        $neworder = $this->orders->create();
        $neworder->num = $order_num;
        $neworder->date = date();
        $neworder->status = 'a';
        $neworder->total = 0;
        $this->orders->add($neworder);

        redirect('/order/display_menu/' . $order_num);
    }

    // show the menu
    function display_menu($order_num = null) {
        if ($order_num == null)
            redirect('/order/neworder');
        
        //set view
        $this->data['pagebody'] = 'show_menu';
        
        //make title, with order num, and total of the order
        $this->data['order_num'] = $order_num;
        $thetotal = $this->orders->total($order_num);
        $this->data['title'] = "Order #".$order_num." (".$thetotal.")";

        // Make the columns
        $this->data['meals'] = $this->make_column('m');
        $this->data['drinks'] = $this->make_column('d');
        $this->data['sweets'] = $this->make_column('s');

	// Bit of a hokey patch here, to work around the problem of the template
	// parser no longer allowing access to a parent variable inside a
	// child loop - used for the columns in the menu display.
	// this feature, formerly in CI2.2, was removed in CI3 because
	// it presented a security vulnerability.
	// 
	// This means that we cannot reference order_num inside of any of the
	// variable pair loops in our view, but must instead make sure
	///$ORDER/ that any such substitutions we wish make are injected into the 
	// variable parameters
	// Merge this fix into your origin/master for the lab!
	$this->hokeyfix($this->data['meals'],$order_num);
	$this->hokeyfix($this->data['drinks'],$order_num);
	$this->hokeyfix($this->data['sweets'],$order_num);
	// end of hokey patch
	
        $this->render();
    }

    // inject order # into nested variable pair parameters
    function hokeyfix($varpair,$order) {
	foreach($varpair as &$record)
	    $record->order_num = $order;
    }
    
    // make a menu ordering column
    function make_column($category) {
        return $this->menu->some('category', $category);
    }

    // add an item to an order
    function add($order_num, $item) {
        $this->orders->add_item($order_num, $item); //add item to orderitems table, as implemented in model
        redirect('/order/display_menu/' . $order_num);
    }

    // checkout
    function checkout($order_num) {
        
        //Set view parameters for the checkout page, which uses the show_order view
        $this->data['title'] = 'Checking Out';
        $this->data['pagebody'] = 'show_order';
        $this->data['order_num'] = $order_num;
        $this->data['total'] = number_format($this->orders->total($order_num), 2);
        
        $items = $this->orderitems->group($order_num); // get the orderitems records with $order_num as the order
        foreach ($items as $item)
        {
            $menuitem = $this->menu->get($item->item); //get (first?) table record with the value $item->item
            $item->code = $menuitem->name;
        }
        $this->data['items'] = $items;
        $this->data['okornot'] = $this->orders->validate($order_num);

        $this->render();
    }

    // proceed with checkout
    function commit($order_num) {
        if (!$this->orders->validate($order_num))
            redirect('/order/display_menu/' . $order_num); //return to menu if not a valid order...       
        
        //set the order to completed, and set the date and total too
        $record = $this->orders->get($order_num);
        $record->date = date(DATE_ATOM);
        $record->status = 'c';
        $record->total = $this->orders->total($order_num);
        $this->orders->update($record);
        
        redirect('/');
    }

    // cancel the order
    function cancel($order_num) {
        $this->orders->flush($order_num); //delete orderitems records that were created in the process of creating this order
        $record = $this->orders->get($order_num);
        $record->status = 'x';
        $this->orders->update($record);
        redirect('/');
    }

}
