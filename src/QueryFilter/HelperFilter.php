<?php

namespace eloquentFilter\QueryFilter;

use Illuminate\Support\Arr;

/**
 * Trait HelperFilter.
 */
trait HelperFilter
{
    /**
     * @param array $arr
     *
     * @return bool
     */
    private static function isAssoc(array $arr)
    {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * @param       $field
     * @param array $args
     *
     * @return array|null
     */
    private function convertRelationArrayRequestToStr($field, array $args)
    {
        $arg_last = Arr::last($args);

        if (is_array($arg_last)) {
            $out = Arr::dot($args, $field.'.');
            if (!self::isAssoc($arg_last)) {
                $out = Arr::dot($args, $field.'.');
                foreach ($out as $key => $item) {
                    $index = $key;
                    for ($i = 0; $i <= 9; $i++) {
                        $index = rtrim($index, '.'.$i);
                    }
                    $new[$index][] = $out[$key];
                }
                $out = $new;
            }
        } else {
            $out = Arr::dot($args, $field.'.');
        }

        return $out;
    }

    /**
     * @param array|null $request
     */
    protected function setRequest($request): void
    {
        if (!empty($request['page'])) {
            unset($request['page']);
        }
        $request = array_filter($request, function ($value) {
            return !is_null($value) && $value !== '';
        });

        foreach ($request as $key => $item) {
            if (is_array($item)) {
                if (array_key_exists('start', $item) && array_key_exists('end', $item)) {
                    if (!isset($item['start']) && !isset($item['end'])) {
                        unset($request[$key]);
                    }
                }
            }
        }

        $this->request = $request;
    }

    /**
     * @return array|null
     */
    protected function getRequest(): ?array
    {
        return $this->request;
    }

    /**
     * @param array|null $ignore_request
     * @param array|null $accept_request
     * @param            $builder_model
     *
     * @return array|null
     */
    protected function setFilterRequests(array $ignore_request = null, array $accept_request = null, $builder_model): ?array
    {
        if (!empty($this->getRequest())) {
            if (!empty($ignore_request)) {
                $this->setIgnoreRequest($ignore_request);
            }
            if (!empty($accept_request)) {
                $this->setAcceptRequest($accept_request);
            }
        }
        if (!empty($this->getRequest())) {
            foreach ($this->getRequest() as $name => $value) {
                if (is_array($value) && method_exists($builder_model, $name)) {
                    if (self::isAssoc($value)) {
                        unset($this->request[$name]);
                        $out = $this->convertRelationArrayRequestToStr($name, $value);
                        $this->setRequest(array_merge($out, $this->request));
                    }
                }
            }
        }

        return $this->getRequest();
    }

    /**
     * @param array $ignore_request
     */
    private function setIgnoreRequest(array $ignore_request): void
    {
        $data = Arr::except($this->getRequest(), $ignore_request);
        $this->setRequest($data);
    }

    private function setAcceptRequest(array $accept_request): void
    {
        if (!empty($accept_request)) {
            $req = $this->array_slice_keys($this->getRequest(), $accept_request);
            $this->setRequest($req);
        }
    }

    /**
     * @param null $index
     *
     * @return array|mixed|null
     */
    public function filterRequests($index = null)
    {
        if (!empty($index)) {
            return $this->getRequest()[$index];
        }

        return $this->getRequest();
    }

    public function array_slice_keys($array, $keys = null)
    {
        if (empty($keys)) {
            $keys = array_keys($array);
        }
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        if (!is_array($array)) {
            return [];
        } else {
            return array_intersect_key($array, array_fill_keys($keys, '1'));
        }
    }
}
