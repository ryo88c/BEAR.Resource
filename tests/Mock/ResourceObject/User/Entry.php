<?php

namespace testworld\ResourceObject\User;

use BEAR\Resource\Object as ResourceObject,
    BEAR\Resource\AbstractObject,
    BEAR\Resource\Resource;


class Entry extends AbstractObject
{

    /**
     * @param Resource $resource
     */
    public function __construct(Resource $resource = null)
    {
        if (is_null($resource)) {
            $resurce = include dirname(dirname(dirname(__DIR__))) . '/script/resource.php';
        }
        $this->resource = $resource;
    }

    private $entries = array(
        100 => array('id' => 100, 'title' => "Entry1"),
        101 => array('id' => 101, 'title' => "Entry2"),
        102 => array('id' => 102, 'title' => "Entry3"),
    );
     
    /**
     * @param id
     *
     * @return array
     */
    public function onGet()
    {
//         $this['count'] =  count($this->entries);
//         $this['entry'] =  $this->entries;
        return $this->entries;
    }

    public function onLinkComment(array $body)
    {
        $request = $this->resource
        ->get->uri('app://self/User/Entry/Comment')->withQuery(['entry_id' => $body['id']])->request();
        return $request;
    }
}
