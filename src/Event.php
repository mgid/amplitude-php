<?php
namespace Zumba\Amplitude;

class Event implements \JsonSerializable
{
    /**
     * Array of data for this event
     *
     * @var array
     */
    protected $data = [];

    /**
     * Array of built-in properties used for events, and the data type for each one.
     *
     * @var array
     */
    protected $availableVars = [
        'user_id' => 'string',
        'device_id' => 'string',
        'event_type' => 'string',
        'time' => 'int',
        'event_properties' => 'array',
        'user_properties' => 'array',
        'app_version' => 'string',
        'platform' => 'string',
        'os_name' => 'string',
        'os_version' => 'string',
        'device_brand' => 'string',
        'device_manufacturer' => 'string',
        'device_model' => 'string',
        'device_type' => 'string',
        'carrier' => 'string',
        'country' => 'string',
        'region' => 'string',
        'city' => 'string',
        'dma' => 'string',
        'language' => 'string',
        'price' => 'float',
        'quantity' => 'int',
        'revenue' => 'float',
        'productId' => 'string',
        'revenueType' => 'string',
        'location_lat' => 'float',
        'location_lng' => 'float',
        'ip' => 'string',
        'idfa' => 'string',
        'adid' => 'string',
    ];

    /**
     * Constructor
     *
     * @param array $data Initial data to set on the event
     */
    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->set($data);
        }
    }


    /**
     * Set a value in the event.
     *
     * If the name matches one of the built-in event properties, such as user_id, device_id, etc. OR matches the camel
     * case version like userId, deviceId etc. - it will set the built-in property, casting the value to the
     * appropriate type for that property
     *
     * If the name does not match either underscore or camcelcase version of a built in event property name, it will
     * set the value in the event_properties array.
     *
     * It also accepts an array of key => value pairs for the first argument, to pass in an array of properties to set.
     *
     * All of these are equivelent, and any of these would set the event property "device_brand" to "HTC":
     *
     * <code>
     * $event->set('device_brand', 'HTC');
     * $event->set('deviceBrand', 'HTC');
     * // Object magic methods
     * $event->device_brand = 'HTC';
     * $event->deviceBrand = 'HTC';
     * // setting array
     * $event->set(['device_brand' => 'HTC']);
     * $event->set(['deviceBrand' => 'HTC']);
     * </code>
     *
     * All of the above are equivelent, use whatever is most appropriate for your project / situation.
     *
     * Note that only built-in event properties are normalized to match the built-in name.  Custom properties that get
     * set in event_properties are not normalized.  Meaning if you use a camelcase name, name with spaces in it, etc,
     * it will use that name as-is without attempting to normalize.
     *
     * @param string|array $name If array, will set key:value pairs
     * @param string $value Not used if first argument is an array
     * @return \Zumba\Amplitude\Event
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->set($key, $val);
            }
            return $this;
        }
        $name = $this->normalize($name);
        if (!isset($this->availableVars[$name])) {
            // treat it like an event_property
            $this->data['event_properties'][$name] = $value;
            return $this;
        }

        switch ($this->availableVars[$name]) {
            case 'string':
                $value = (string)$value;
                break;
            case 'int':
                $value = (int)$value;
                break;
            case 'float':
                $value = (float)$value;
                break;
            case 'array':
                $value = (array)$value;
                break;
        }
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * Gets the event property, either from built-in event properties or the custom properties from event_properties.
     *
     * As with the set() method, for built-in event properties, can use camelcase OR underscore and either one will
     * work.  This is not the case for custom event properties however.
     *
     * For example, any of these calls will get the value of device_brand:
     *
     * <code>
     * $event->get('device_brand');
     * $event->get('deviceBrand');
     * // Magic methods work too:
     * $event->device_brand;
     * $event->deviceBrand;
     * </code>
     *
     * If no value found, returns null.
     *
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        $name = $this->normalize($name);
        if (isset($this->data[$name])) {
            return $this->data[$name];
        } elseif (isset($this->data['event_properties'][$name])) {
            return $this->data['event_properties'][$name];
        }
        return null;
    }

    /**
     * Unset event property, either from built-in event properties or the custom properties from event_properties.
     *
     * As with the set() method, for built-in event properties, can use camelcase OR underscore and either one will
     * work.  This is not the case for custom event properties however.
     *
     * For example, any of these calls will unset the main built-in property device_brand:
     *
     * <code>
     * $event->unsetProperty('device_brand');
     * $event->unsetProperty('deviceBrand');
     * // Magic methods work too:
     * unset($event->device_brand);
     * unset($event->deviceBrand);
     * </code>
     *
     * @param string $name
     * @return \Zumba\Amplitude\Event
     */
    public function unsetProperty($name)
    {
        $name = $this->normalize($name);
        if (isset($this->availableVars[$name])) {
            unset($this->data[$name]);
        } elseif (isset($this->data['event_properties'])) {
            unset($this->data['event_properties'][$name]);
        }
        return $this;
    }

    /**
     * Check if event property is set, either from built-in event properties or custom properties from event_properties
     *
     * As with the set() method, for built-in event properties, can use camelcase OR underscore and either one will
     * work.  This is not the case for custom event properties however.
     *
     * For example, any of these calls will check if the built in property for device_brand is set:
     *
     * <code>
     * $event->isPropertySet('device_brand');
     * $event->isPropertySet('deviceBrand');
     * // Magic methods work too:
     * isset($event->device_brand);
     * isset($event->deviceBrand);
     * </code>
     *
     * @param string $name
     * @return \Zumba\Amplitude\Event
     */
    public function isPropertySet($name)
    {
        $name = $this->normalize($name);
        return isset($this->data[$name]) || isset($this->data['event_properties'][$name]);
    }

    /**
     * Magic method to set the value.
     *
     * See the set() method.
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Magic method to get the value
     *
     * See the get() method
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Unset event property
     *
     * See the unsetProperty() method
     *
     * @param string $name
     */
    public function __unset($name)
    {
        $this->unsetProperty($name);
    }

    /**
     * Magic method to see if name is set
     *
     * Uses same normalization on the name as the set method, where it will match built-in properties for either
     * camelcased or underscore version of property
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return $this->isPropertySet($name);
    }

    /**
     * Normalized the name, by attempting to camelcase / underscore it to see if it matches any built-in property names.
     *
     * If it matches a built-in property name, will return the normalized property name.  Otherwise returns the name
     * un-modified.
     *
     * @param string $name
     * @return string
     */
    protected function normalize($name)
    {
        if (isset($this->availableVars[$name])) {
            return $name;
        }
        if (preg_match('/^[a-zA-Z_]+$/', $name)) {
            // No spaces or unexpected vars, this could be camelcased version or underscore version of a built-in
            // var name, check to see if it matches
            $underscore = Inflector::underscore($name);
            if (isset($this->availableVars[$underscore])) {
                return $underscore;
            }
            // In case it is one of the camel-cased versions
            $camel = Inflector::camelCase($name);
            if (isset($this->availableVars[$camel])) {
                return $camel;
            }
        }
        // Could not find name, just use original un-altered, probably used in event_properties
        return $name;
    }

    /**
     * Convert the event to array format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * JSON serialize
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
}