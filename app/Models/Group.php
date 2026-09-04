<?php

namespace App\Models;

/**
 * Group model — Redis Hash: group:{group_id}
 */
class Group
{
    public string $group_id;
    public string $name;
    public string $avatar_url;
    public string $owner_id;
    public string $description;
    public int    $max_members;
    public int    $created_at;
    public int    $last_msg_at;

    public function __construct(array $data = [])
    {
        $this->group_id    = $data['group_id']    ?? '';
        $this->name        = $data['name']        ?? '';
        $this->avatar_url  = $data['avatar_url']  ?? '';
        $this->owner_id    = $data['owner_id']    ?? '';
        $this->description = $data['description'] ?? '';
        $this->max_members = (int) ($data['max_members'] ?? 50);
        $this->created_at  = (int) ($data['created_at']  ?? 0);
        $this->last_msg_at = (int) ($data['last_msg_at'] ?? 0);
    }

    public function toArray(): array
    {
        return [
            'group_id'    => $this->group_id,
            'name'        => $this->name,
            'avatar_url'  => $this->avatar_url,
            'owner_id'    => $this->owner_id,
            'description' => $this->description,
            'max_members' => $this->max_members,
            'created_at'  => $this->created_at,
            'last_msg_at' => $this->last_msg_at,
        ];
    }
}
