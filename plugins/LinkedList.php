<?php

  class node
  {

    public $next = null;
    public $prev = null;
    public $dataObj = null;
    public $idx = null;

    function __construct($dataObj, $n,
            $p, $idx)
    {
      $this->dataObj = $dataObj;
      $this->next = $n;
      $this->prev = $p;
      $this->idx = $idx;
    }

    function nodeDbg()
    {
      echo "dataObj: ", $this->dataObj, "<br/>";
      echo "idx: ", $this->idx, "<br/>";
      if ($this->next != null)
        echo "next: ", $this->next->dataObj, "<br/>";
      else
        echo "next: null <br/>";
      if ($this->prev != null)
        echo "prev: ", $this->prev->dataObj, "<br/>";
      else
        echo "prev: null <br/>";
      echo "<br/>";
    }

  }

  class LinkedList
  {

    var $head;
    var $nodeCount = 0;

    function __construct()
    {
      $this->head = NULL;
    }

    function ListInsert($x)
    {
      $x->prev = null;
      $x->next = null;
      if (is_null($this->head))
      {
        $this->head = $x;
      }
      else
      {
        $x->next = $this->head;
        if (!is_null($this->head))
          $this->head->prev = $x;
        $this->head = $x;
        $this->head->prev = NULL;
      }
      $this->nodeCount++;

      /*
        if($x->prev == NULL)
        $x->prev = $x;
        if($x->next == NULL)
        $x->next = $x;
       */
    }

    // maintain pointer to end node?
    function ListAppend($x)
    {
      if ($x != null)
      {
        $x->prev = null;
        $x->next = null;
        if (is_null($this->head))
        {
          $this->head = $x;
        }
        else
        {
          $next = $this->head;
          while ($next->next != null)
          {
            $next = $next->next;
          }
          $x->prev = $next;
          $next->next = $x;
        }
        $this->nodeCount++;
      }
      else
      {
        echo "null no node to append<br>";
      }
      /*
        if($x->prev == NULL)
        $x->prev = $x;
        if($x->next == NULL)
        $x->next = $x;
       */
    }

    function SortMe()
    {
      // need to reset the hrefs in order!
      $tmp = array();
      $next = $this->head;
      $tmp[] = $next;

      while ($next != null)
      {
        $next = $next->next;
        if ($next != null)
        {
          $tmp[] = $next;
        }
      }

      $this->head = null;
      $cnt = count($tmp);
      for ($i = 0; $i < $cnt; $i++)
      {
        $tmp[$i]->prev = null;
        $tmp[$i]->next = null;
        $this->ListInsertInOrder($tmp[$i]);
      }
    }

    function ListInsertInOrder($x)
    {
      if ($this->head == null)
      {
        $this->head = $x;
      }
      elseif ($this->head->dataObj > $x->dataObj)
      {
        $x->next = $this->head;
        $x->next->prev = $x;
        $this->head = $x;
      }
      else
      {
        $next = $this->head;
        while ($next->next != null)
        {
          if ($next->next->dataObj > $x->dataObj)
            break;
          $next = $next->next;
        }
        $tmp = $next->next;
        $next->next = $x;
        $x->prev = $next;
        $x->next = $tmp;
      }
      $this->nodeCount++;
    }

    function RemoveNode($x)
    {
      if (!is_null($x->prev))
        $x->prev->next = $x->next;
      else
        $this->head = $x->next;

      if (!is_null($x->next))
        $x->next->prev = $x->prev;
      $this->nodeCount--;
    }

    function getHead()
    {
      return($this->head);
    }

    function NewHead($x)
    {
      $x->next = $this->head;
      if (!is_null($this->head))
        $this->head->prev = $x;
      $this->head = $x;
      $this->head->prev = null;
      $this->nodeCount++;
    }

    function NodePrepend($old, $new)
    {
      //echo "nodePrepend<br>";
      $prev = $old->prev;
      if ($prev != null)
      {
        $prev->next = $new;
      }
      else
      {
        $this->head = $new;
      }
      $new->prev = $prev;
      $new->next = $old;
      $old->prev = $new;
      $this->nodeCount++;

      $tmp = $this->head;
      while ($tmp->next != null)
      {
        //$tmp->dbg();
        $tmp = $tmp->next;
      }
    }

    function PrevNode($node)
    {
      return $node->prev;
    }

    function NextNode($node)
    {
      return $node->next;
    }

    function NodeAppend($old, $new)
    {
      //echo "nodeAppend<br>";
      $oldnext = $old->next;

      $new->prev = $old;
      $new->next = $oldnext;

      $old->next = $new;
      $oldnext->prev = $new;

      $this->nodeCount++;
    }

    function AttachAtListEnd($x)
    {
      //echo "AttachAtLIstEnd<br>";
      if ($this->head == null)
      {
        $this->head = $x;
        $x->prev = null;
      }
      else
      {
        $tmp = $this->head;
        while ($tmp->next != null)
          $tmp = $tmp->next;
        $tmp->next = $x;
        $x->prev = $tmp;
      }
      $this->nodeCount++;
    }

    function getObjects()
    {
      $ret = array();
      $x = $this->head;
      $flag = true;

      // use while or nodeCount (if circularly linked)
      while (isset($x) && $flag)
      {
        $ret[] = $x;
        if (is_null($x->next))
          $flag = false;
        if (!$flag)
          break;
        $x = $x->next;
      }
      return $ret;
    }

//belongs not here?
    function getHrefs()
    {
      $ret = array();
      $x = $this->head;
      $flag = true;
      while (isset($x) && $flag)
      {
        $ret[] = $x->getHref();
        if (is_null($x->next))
          $flag = false;
        $x = $x->next;
      }
      return $ret;
    }

    function getList()
    {
      $ret = array();
      $x = $this->head;
      $flag = true;
      while (isset($x) && $flag)
      {
        $ret[] = $x;
        if (is_null($x->next))
          $flag = false;
        $x = $x->next;
      }
      return $ret;
    }

  }

  /*
    $nodeHash = array();

    $n1 = new node("uga buga",null,null,0);
    $n2 = new node("you mamma",null,null,1);
    $n3 = new node("nixon",null,null,2);
    $n4 = new node("bush",null,null,3);
    $n5 = new node("fdr",null,null,4);

    $list = new LinkedList();

    $list->ListInsert($n1);
    $list->ListInsert($n2);
    $list->ListInsert($n3);
    $list->ListInsert($n4);
    $list->ListInsert($n5);

    $objs = $list->getObjects();
    echo "count(objs): ", count($objs), "<br/>";
    foreach ($objs as $anobj)
    {
    echo "n" , $anobj->idx , ": " , $anobj->dataObj, " <br/>";
    $nodeHash[$anobj->dataObj] = $anobj;
    }

    echo "<br/>";
    $list->SortMe();
    $objs = $list->getObjects();
    foreach ($objs as $anobj)
    {
    echo "n" , $anobj->idx , ": " , $anobj->dataObj, " <br/>";
    $nodeHash[$anobj->dataObj] = $anobj;
    }


    $aprev = $n3->prev;
    echo "<br/> n3's previous: $aprev->dataObj <br/>";

    $bushes = $nodeHash['bush']->next;
    if($bushes != NULL)
    echo "<br/> bushes's next: $bushes->dataObj <br/>";
    else
    echo "bushes next is null<br/>";
   */
?>
