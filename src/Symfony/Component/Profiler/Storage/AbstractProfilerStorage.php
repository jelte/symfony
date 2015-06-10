<?php

namespace Symfony\Component\Profiler\Storage;

use Symfony\Component\Profiler\ProfileInterface;
use Symfony\Component\Profiler\Encoder\ProfileEncoderInterface;

abstract class AbstractProfilerStorage implements ProfilerStorageInterface
{
    private $encoders = array();

    public function write(ProfileInterface $profile)
    {
        $encoder = $this->determineEncoder(get_class($profile));

        $data = $encoder->encode($profile);

        $indexedData = array_intersect_key($data, array_fill_keys($encoder->getIndexes(), null));

        $res = $this->doWrite($profile->getToken(), $data, $indexedData);
        foreach ($profile->getChildren() as $childProfile) {
            $this->write($childProfile);
        }

        return $res;
    }

    public function read($token, ProfileInterface $parent = null)
    {
        $data = $this->doRead($token);

        if (empty($data['_class'])) {
            return;
        }

        $encoder = $this->determineEncoder($data['_class']);

        $profile = $encoder->decode($data);
        foreach (unserialize(base64_decode($data['children'])) as $childProfileToken) {
            $childProfile = $this->read($childProfileToken, $profile);
            $profile->addChild($childProfile);
        }

        if (isset($data['parent_token']) && null !== $data['parent_token'] && null === $parent) {
            $profile->setParent($this->read($data['parent_token']));
        }

        return $profile;
    }

    public function addEncoder(ProfileEncoderInterface $encoder)
    {
        $this->encoders[] = $encoder;
    }

    abstract protected function doWrite($token, array $data, array $indexedData);

    abstract protected function doRead($token);

    /**
     * @param $class
     *
     * @return ProfileEncoderInterface
     *
     * @throws \Exception
     */
    private function determineEncoder($class)
    {
        /** @var ProfileEncoderInterface $encoder */
        foreach ($this->encoders as $encoder) {
            if ($encoder->supports($class)) {
                return $encoder;
            }
        }
        throw new \Exception(sprintf('No converter found for "%s"', $class));
    }
}
