<?php

namespace election\Resource;

/**
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */
class Referendum extends \Measure
{
    /**
     *
     * @var \Variable\String
     */
    protected $description;
    
    protected $table = 'elect_referendum';

    public function __construct()
    {
        parent::__construct();
        $this->description = new \Variable\String(null, 'description');
    }

}