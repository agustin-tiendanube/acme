<?php

namespace Kelunik\Acme;

use Amp\Dns\NoRecordException;
use Amp\Dns\Record;
use Amp\Dns\ResolutionException;
use Amp\Dns\Resolver;
use Amp\Failure;
use Amp\Success;
use PHPUnit\Framework\TestCase;

class Dns01VerificationTest extends TestCase {
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resolver;

    /**
     * @var Verifiers\Dns01
     */
    private $verifier;

    public function setUp() {
        $this->resolver = $this->getMockBuilder(Resolver::class)->getMock();
        $this->verifier = new Verifiers\Dns01($this->resolver);
    }

    /**
     * @test
     * @expectedException \Kelunik\Acme\AcmeException
     * @expectedExceptionMessage Verification failed, no TXT record found for '_acme-challenge.example.com'.
     */
    public function failsOnDnsNotFound() {
        $this->resolver->method("query")->willReturn(new Failure(new NoRecordException));
        \Amp\Promise\wait($this->verifier->verifyChallenge("example.com", "foobar"));
    }

    /**
     * @test
     * @expectedException \Kelunik\Acme\AcmeException
     * @expectedExceptionMessage Verification failed, couldn't query TXT record of '_acme-challenge.example.com'
     */
    public function failsOnGeneralDnsIssue() {
        $this->resolver->method("query")->willReturn(new Failure(new ResolutionException));
        \Amp\Promise\wait($this->verifier->verifyChallenge("example.com", "foobar"));
    }

    /**
     * @test
     * @expectedException \Kelunik\Acme\AcmeException
     * @expectedExceptionMessage Verification failed, please check DNS record for '_acme-challenge.example.com'.
     */
    public function failsOnWrongPayload() {
        $this->resolver->method("query")->willReturn(new Success([new Record("xyz", Record::TXT, 300)]));
        \Amp\Promise\wait($this->verifier->verifyChallenge("example.com", "foobar"));
    }

    /**
     * @test
     */
    public function succeedsOnRightPayload() {
        $this->resolver->method("query")->willReturn(new Success([new Record("foobar", Record::TXT, 300)]));
        \Amp\Promise\wait($this->verifier->verifyChallenge("example.com", "foobar"));
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     * @expectedException \TypeError
     */
    public function failsWithDomainNotString() {
        \Amp\Promise\wait($this->verifier->verifyChallenge(null, ""));
    }

    /**
     * @test
     * @expectedException \TypeError
     */
    public function failsWithPayloadNotString() {
        \Amp\Promise\wait($this->verifier->verifyChallenge("example.com", null));
    }
}