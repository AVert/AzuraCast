<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="role")
 * @Entity
 */
class Role extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->users = new ArrayCollection;
        $this->actions = new ArrayCollection;
    }
    
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="name", type="string", length=100) */
    protected $name;

    /** @ManyToMany(targetEntity="User", mappedBy="roles")*/
    protected $users;
    
    /**
     * @ManyToMany(targetEntity="Action", inversedBy="roles")
     * @JoinTable(name="role_has_action",
     *      joinColumns={@JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="action_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $actions;
}