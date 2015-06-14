<?php


namespace Symfony\Component\Profiler\Tests\Mock;


use Symfony\Component\Profiler\Encoder\AbstractProfileEncoder;
use Symfony\Component\Profiler\Encoder\ProfileEncoderInterface;
use Symfony\Component\Profiler\ProfileInterface;

class MockEncoder extends AbstractProfileEncoder implements ProfileEncoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($className)
    {
        return parent::doesSupports($className, 'Symfony\Component\Profiler\Tests\Mock\MockProfile');
    }

    /**
     * {@inheritdoc}
     */
    public function encode(ProfileInterface $profile)
    {
        return parent::doEncode($profile);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(array $data)
    {
        $profile = new MockProfile(
            $data['token'],
            $data['time']
        );

        return parent::doDecode($profile, $data);
    }
}