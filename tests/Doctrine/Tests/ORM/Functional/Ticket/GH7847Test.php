<?php

namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class GH7847Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(Note::class),
                $this->_em->getClassMetadata(Person::class),
                $this->_em->getClassMetadata(Staff::class)
            ]
        );
    }

    /**
     * @group GH7847
     */
    public function testIssue()
    {
        // Create Staff Member
        $staff = new Staff();
        $staff = $staff->setJobTitle('dev')
            ->setFirstName('Rhys');
        $this->_em->persist($staff);
        $this->_em->flush();
        $this->_em->clear();

        /** @var Staff $staff */
        $staff = $this->_em->find(get_class($staff), $staff->getId());

        // Create a new note updated by Staff Member
        $note = new Note();
        $note = $note->setUpdatedBy($staff);
        $this->_em->persist($note);
        $this->_em->flush();
        $this->_em->clear();

        // A note now exists updated by staff member

        /** @var Note $note */
        $note = $this->_em->find(get_class($note), $note->getId());

        $this->assertInstanceOf(Person::class, $note->getUpdatedBy());

        // We would expect the ID to be of 1
        $this->assertEquals($staff->getId(), $note->getUpdatedBy()->getId());
    }
}

/**
 * @Entity
 */
class Note
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Person", inversedBy="id")
     * @JoinColumn(name="last_updated_by", referencedColumnName="id", onDelete="RESTRICT")
     * @var Person
     */
    private $lastUpdatedBy;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Person
     */
    public function getUpdatedBy(): Person
    {
        return $this->lastUpdatedBy;
    }

    /**
     * @param Person $staff
     * @return self
     */
    public function setUpdatedBy(Person $staff): self
    {
        $this->lastUpdatedBy = $staff;
        return $this;
    }
}


/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *     "staff" = "Staff"
 * })
 */
abstract class Person
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer", options={"unsigned"=true})
     * @var int
     */
    protected $id;

    /**
     * @Column(name="first_name", type="string", length=50, nullable=false)
     * @var string
     */
    protected $firstName;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return self
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }
}

/**
 * @Entity
 */
class Staff extends Person
{
    /**
     * @Column(name="job_title", type="string", length=124, nullable=false)
     * @var string
     */
    protected $jobTitle = '';

    /**
     * @return string
     */
    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    /**
     * @param string $jobTitle
     * @return self
     */
    public function setJobTitle(string $jobTitle): self
    {
        $this->jobTitle = $jobTitle;
        return $this;
    }
}
