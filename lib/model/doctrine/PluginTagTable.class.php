<?php
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class PluginTagTable extends Doctrine_Table
{
    /**
    * Returns all tags, eventually with a limit option.
    * The first optionnal parameter permits to add some restrictions on the
    * objects the selected tags are related to.
    * The second optionnal parameter permits to restrict the tag selection with
    * different criterias
    *
    * @param      Doctrine_Query    $c
    * @param      array       $options
    * @return     array
    */
    public static function getAllTagName(Doctrine_Query $q = null, $options = array())
    {
        if ($q == null)
        {
            $q = Doctrine_Query::create();
        }
        
        if (!$q->getDqlPart('select'))
        {
          $q->select('t.name');
        }
        if (!$q->getDqlPart('from'))
        {
          $q->from('Tag t INDEXBY t.name');
			  }	

        if (isset($options['limit']))
        {
            $q->limit($options['limit']);
        }
        
        if (isset($options['like']))
        {
            $q->addWhere('t.name like ?', $options['like']);
        }
        
        if (isset($options['triple']))
        {
            $q->addWhere('t.is_triple = ?', $options['triple']);
        }
        
        if (isset($options['namespace']))
        {
            $q->addWhere('t.triple_namespace = ?', $options['namespace']);
        }
        
        if (isset($options['key']))
        {
            $q->addWhere('t.triple_key = ?', $options['key']);
        }
        
        if (isset($options['value']))
        {
            $q->addWhere('t.triple_value = ?', $options['value']);
        }
        
//        return array_keys($q->orderBy('t.name')->execute(array(), Doctrine::HYDRATE_ARRAY));
        return array_keys($q->execute(array(), Doctrine::HYDRATE_ARRAY));
    }

    /**
    * Returns all tags, sorted by name, with their number of occurencies.
    * The first optional parameter permits to add some restrictions on the
    * objects the selected tags are related to.
    * The second optional parameter permits to restrict the tag selection with
    * different criterias
    *
    * @param      Doctrine_Query    $c
    * @param      array       $options
    * @return     array
    */
    public static function getAllTagNameWithCount(Doctrine_Query $q = null, $options = array())
    {
        if ($q == null)
        {
            $q = Doctrine_Query::create();
        }
        
        $q->select('tg.tag_id, t.name, COUNT(tg.id) AS t_count,t.triple_value');
        
        //allows to pass more complex queries with a lot of joins
        if (!$q->getDqlPart('from'))
        {
          $q->from('Tagging tg, tg.Tag t');    
        }

        if (isset($options['limit']))
        {
            $q->limit($options['limit']);
        }

        if (isset($options['model']))
        {
            $q->addWhere('tg.taggable_model = ?', $options['model']);
        }

        if (isset($options['like']))
        {
            $q->addWhere('t.name like ?', $options['like']);
        }

        if (isset($options['triple']))
        {
            $q->addWhere('t.is_triple = ?', $options['triple']);
        }

        if (isset($options['namespace']))
        {
            $q->addWhere('t.triple_namespace = ?', $options['namespace']);
        }

        if (isset($options['key']))
        {
            $q->addWhere('t.triple_key = ?', $options['key']);
        }

        if (isset($options['value']))
        {
            $q->addWhere('t.triple_value = ?', $options['value']);
        }
        
        if (isset($options['min_tags_count']))
        {
            $q->having('t_count >= ?', $options['min_tags_count']);
        }
        
        $q->groupBy('tg.tag_id') // , t.name ?
          ->orderBy('t_count DESC, t.name ASC')
        ;
        
        $rs = $q->execute(array(), Doctrine::HYDRATE_ARRAY);
        
        $tags = array();
        
        foreach($rs as $tag)
        {
            $name = isset($options['triple']) ? $tag['Tag']['triple_value'] :$tag['Tag']['name'];
            $tags[$name] = $tag['t_count'];
        }

        if (!isset($options['sort_by_popularity']) || (true !== $options['sort_by_popularity']))
        {
            ksort($tags);
        }

        return $tags;
    }

    /**
    * Returns the names of the models that have instances tagged with one or
    * several tags. The optionnal parameter might be a string, an array, or a
    * comma separated string
    *
    * @param      mixed       $tags
    * @return     array
    */
    public static function getModelsNameTaggedWith($tags = array())
    {
        if (is_string($tags))
        {
            if (false !== strpos($tags, ','))
            {
                $tags = explode(',', $tags);
            }
            else
            {
                $tags = array($tags);
            }
        }
        
        $q = Doctrine_Query::create()
                           ->select('tg.taggable_model, tg.taggable_id')
                           ->from('Tagging tg, Tag t')
                           ->where('t.name in ?', $tags)
                           ->having('count(t.id) > ?', count($tags))
                           ->groupBy('tg.taggable_id')
                           ->execute(array(), Doctrine::FETCH_ARRAY);
                           
        foreach($q as $cc)
        {
            $models[] = $cc[1];
        }
        
        return $models;
    }

    /**
    * Returns the most popular tags with their associated weight. See
    * TaggableToolkit::normalize for more details.
    *
    * The first optionnal parameter permits to add some restrictions on the
    * objects the selected tags are related to.
    * The second optionnal parameter permits to restrict the tag selection with
    * different criterias
    *
    * @param      Criteria    $c
    * @param      array       $options
    * @return     array
    */
    public static function getPopulars($q = null, $options = array())
    {
        if ($q == null)
        {
            $q = Doctrine_Query::create()->limit(sfConfig::get('app_sfDoctrineActAsTaggablePlugin_limit', 100));
        }
        
        $all_tags = self::getAllTagNameWithCount($q, $options);
        return TaggableToolkit::normalize($all_tags);
    }

    /**
    * Returns the tags that are related to one or more other tags, with their
    * associated weight (see TaggableToolkit::normalize for more
    * details).
    * The "related tags" of one tag are the ones which have at least one
    * taggable object in common.
    *
    * The first optionnal parameter permits to add some restrictions on the
    * objects the selected tags are related to.
    * The second optionnal parameter permits to restrict the tag selection with
    * different criterias
    *
    * @param      mixed       $tags
    * @param      array       $options
    * @return     array
    */
    public static function getRelatedTags($tags = array(), $options = array())
    {
        $tags = TaggableToolkit::explodeTagString($tags);
        
        if (is_string($tags))
        {
            $tags = array($tags);
        }
        
        $tagging_options = $options;
        
        if (isset($tagging_options['limit']))
        {
          unset($tagging_options['limit']);
        }
        
        $taggings = self::getTaggings($tags, $tagging_options);
        $result = array();
        
        foreach ($taggings as $key => $tagging)
        {
            $tags_rs = Doctrine_Query::create()
                                     ->select('t.name')
                                     ->from('Tag t, t.Tagging tg')
                                     ->where('tg.taggable_model = ?', $key)
                                     ->andWhereNotIn('t.name', $tags)
                                     ->andWhereIn('tg.taggable_id', $tagging)
                                     ->execute(array(), Doctrine::HYDRATE_ARRAY);
            
            foreach ($tags_rs as $tag)
            {
                $tag_name = $tag['name'];
                
                if (!isset($result[$tag_name]))
                {
                    $result[$tag_name] = 0;
                }
                
                $result[$tag_name]++;
            }
        }

        if (isset($options['limit']))
        {
            arsort($result);
            $result = array_slice($result, 0, $options['limit'], true);
        }

        ksort($result);
        
        return TaggableToolkit::normalize($result);
    }

    /**
    * Retrieves the objects tagged with one or several tags.
    *
    * The second optionnal parameter permits to restrict the tag selection with
    * different criterias
    *
    * @param      mixed       $tags
    * @param      array       $options
    * @return     array
    */
    public static function getObjectTaggedWith($tags = array(), $options = array())
    {
        $taggings = self::getTaggings($tags, $options);
        $result = array();

        foreach ($taggings as $key => $tagging)
        {
            $q = Doctrine_Query::create()->from($key . ' t');
            
            if(isset($options['leftJoin']))
            {
                $q->leftJoin($options['leftJoin']);
            }
            
            $hydration = isset($options['hydrate']) ?  $options['hydrate'] : Doctrine::HYDRATE_RECORD;
            
            $objects = $q->whereIn('t.id', $tagging)->execute(array(), $hydration);

            foreach ($objects as $object)
            {
                $result[] = $object;
            }
        }

        return $result;
    }

    /**
    * Retrieve a Doctrine_Query instance for querying tagged model objects.
    *
    * Example:
    *
    * $q = PluginTagTable::getObjectTaggedWithQuery('Article', array('tag1', 'tag2'));
    * $q->orderBy('posted_at DESC');
    * $q->limit(10);
    * $this->articles = $q->execute();
    *
    * @param  string    $model  Taggable model name
    * @param  mixed     $tags   array of tags (can be a string where tags are
    * comma separated)
    * @param  Doctrine_Query  $q     Existing Doctrine_Query to hydrate
    * @return Doctrine_Query
    */
    public static function getObjectTaggedWithQuery($model, $tags = array(), Doctrine_Query $q = null, $options = array())
    {
        $tags = TaggableToolkit::explodeTagString($tags);
    
        if (is_string($tags))
        {
            $tags = array($tags);
        }
        
        if (!class_exists($model) || !PluginTagTable::isDoctrineModelClass($model))
        {
            throw new DoctrineException(sprintf('The class "%s" does not exist, or it is not a model class.', $model));
        }
        
        if (!$q instanceof Doctrine_Query)
        {
            $q = Doctrine_Query::create()->from($model);
        }
        
        $taggings = self::getTaggings($tags, array_merge(array('model' => $model), $options));
        $tagging = isset($taggings[$model]) ? $taggings[$model] : array();
        
        if (empty($tagging))
        {
          $q->where('false');
        }
        else
        {
          $q->whereIn($model . '.id', $tagging);
        }
        
        return $q;
    }

    /**
    * No comment
    */
    public static function isDoctrineModelClass($class)
    {
        return true;
    }
    
    /**
    * Returns the taggings associated to one tag or a set of tags.
    *
    * The second optionnal parameter permits to restrict the results with
    * different criterias
    *
    * @param      mixed       $tags      Array of tag strings or string
    * @param      array       $options   Array of options parameters
    * @return     array
    */
    protected static function getTaggings($tags = array(), $options = array())
    {
        $tags = TaggableToolkit::explodeTagString($tags);

        if (is_string($tags))
        {
            $tags = array($tags);
        }
        
        $q = Doctrine_Query::create()
                           ->select('DISTINCT t.id')
                           ->from('Tag t INDEXBY t.id');
        
        if(count($tags) > 0)
        {
            if (!isset($options['triple']) ) $q->whereIn('t.name', $tags);
            else $q->whereIn('t.triple_value', $tags);
        }

        if (isset($options['triple']))
        {
            $q->addWhere('t.is_triple = ?', $options['triple']);
        }

        if (isset($options['namespace']))
        {
            $q->addWhere('t.triple_namespace = ?', $options['namespace']);
        }

        if (isset($options['key']))
        {
            $q->addWhere('t.triple_key = ?', $options['key']);
        }

        if (isset($options['value']))
        {
            $q->addWhere('t.triple_value = ?', $options['value']);
        }

        if (!isset($options['nb_common_tags']) || ($options['nb_common_tags'] > count($tags)))
        {
            $options['nb_common_tags'] = count($tags);
        }
        
        $tag_ids = $q->execute(array(), Doctrine::HYDRATE_ARRAY);
        
        $q = Doctrine_Query::create()
                           ->select('tg.taggable_id')
                           ->from('Tagging tg')
                           ->whereIn('tg.tag_id', array_keys($tag_ids))
                           ->groupBy('tg.taggable_id')
                           ->having('count(tg.taggable_model) >= ?', $options['nb_common_tags']);

        // Taggable model class option
        if (isset($options['model']))
        {
            if (!class_exists($options['model'])) // TODO: add a test to that's a doctrine model...
            {
                throw new DoctrineException(sprintf('The class "%s" does not exist, or it is not a model class.',
                                      $options['model']));
            }
            
            $q->addWhere('tg.taggable_model = ?', $options['model']);
        }
        else
        {
            $q->addSelect('tg.taggable_model')->addGroupBy('tg.taggable_model');
        }

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        $taggings = array();

        foreach($results as $rs)
        {
            if(isset($options['model']))
            {
                $model = $options['model'];
            }
            else
            {
                $model = $rs['taggable_model'];
            }
            
            if (!isset($taggings[$model]))
            {
                $taggings[$model] = array();
            }

            $taggings[$model][] = $rs['taggable_id'];
        }
        
        return $taggings;
    }

    /**
    * Retrieves a tag by his name. If it does not exist, creates it (but does not
    * save it)
    *
    * @param      String      $tagname
    * @return     Tag
    */
    public static function findOrCreateByTagname($tagname)
    {
        // retrieve or create the tag
        $tag = Doctrine::getTable('Tag')->findOneByName($tagname);

        if (!$tag)
        {
            $tag = new Tag();
            $tag->name = $tagname;
            
            $triple = TaggableToolkit::extractTriple($tagname);
            list($tagname, $triple_namespace, $triple_key, $triple_value) = $triple;

            $tag->triple_namespace = $triple_namespace;
            $tag->triple_key = $triple_key;
            $tag->triple_value = $triple_value;
            $tag->is_triple = !is_null($triple_namespace);
        }
        
        return $tag;
    }
    
    /**
     * Remove Tags without associations in Tagging table
     * 
     * @return array
     */
    public static function purgeOrphans() {
      $q = Doctrine::getTable('Tag')->createQuery('t INDEXBY t.id')
        ->select('t.id')
        ->addWhere('NOT EXISTS (SELECT tg.id FROM Tagging tg WHERE tg.tag_id = t.id)');
        
      $orphans = $q->execute();
      $orphan_data = $orphans->toArray(false);
      $orphans->delete();
      return $orphan_data;
    }
}
