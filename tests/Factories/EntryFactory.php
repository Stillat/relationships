<?php

namespace Tests\Factories;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Statamic;

class EntryFactory
{
    protected $id;

    protected $slug;

    protected $data;

    protected $published;

    protected $order;

    protected $locale;

    protected $origin;

    protected $collection;

    public function __construct()
    {
        $this->reset();
    }

    public function id($id)
    {
        $this->id = $id;

        return $this;
    }

    public function slug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    public function collection($collection)
    {
        $this->collection = $collection;

        return $this;
    }

    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    public function published($published)
    {
        $this->published = $published;

        return $this;
    }

    public function locale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function origin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    public function make()
    {
        $entry = Entry::make()
            ->locale($this->locale)
            ->collection($this->createCollection())
            ->slug($this->slug)
            ->data($this->data)
            ->origin($this->origin)
            ->published($this->published);

        if ($this->id) {
            $entry->id($this->id);
        }

        $this->reset();

        return $entry;
    }

    public function create()
    {
        return tap($this->make())->save();
    }

    private function getLocale()
    {
        $version = Statamic::version();

        if (Str::startsWith($version, '5')) {
            return 'default';
        }

        return 'en';
    }

    protected function createCollection()
    {
        if ($this->collection instanceof StatamicCollection) {
            return $this->collection;
        }

        return Collection::findByHandle($this->collection)
            ?? Collection::make($this->collection)
                ->sites([$this->getLocale()])
                ->save();
    }

    private function reset()
    {

        $this->id = null;
        $this->slug = null;
        $this->data = [];
        $this->published = true;
        $this->order = null;
        $this->locale = $this->getLocale();
        $this->origin = null;
        $this->collection = null;
    }
}
