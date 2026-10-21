<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class NewVisitorNotification extends Notification
{
    use Queueable;

    public $brand;
    public $visitor;

    public function __construct($brand, $visitor)
    {
        $this->brand = $brand;
        $this->visitor = $visitor;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Visitor',
            'message' => 'New visitor on ' . $this->brand->domain,
            'brand_id' => $this->brand->id,
            'visitor_id' => $this->visitor->id,
            'session_id' => $this->visitor->session_id,
            'country_code' => $this->visitor->country_code,
        ];
    }
}
