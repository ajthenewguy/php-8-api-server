<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Services;

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Models\Email;
use Ajthenewguy\Php8ApiServer\Traits\HasConfig;

class MailService
{
    use HasConfig;

    public function __construct()
    {
        $this->setConfig(Application::singleton()->config()->mail);
    }

    public function send(Email $Email, string $to_email, string $to_name = null)
    {
        $message = new \Swift_Message($Email->subject);
        $message->setFrom([$Email->from_email => $Email->from_name])
            ->setTo($to_email, $to_name)
            ->setBody($Email->body);
        
        $successfulRecipientCount = $this->getMailer()->send($message);

        return $successfulRecipientCount;
    }

    private function getTransport()
    {
        $trasport = new \Swift_SmtpTransport(
            $this->config()->get('host'),
            $this->config()->get('port'),
            $this->config()->get('encryption')
        );
        $trasport->setUsername($this->config()->get('username'));
        $trasport->setPassword($this->config()->get('password'));

        return $trasport;
    }

    private function getMailer()
    {
        $transport = $this->getTransport();
        return new \Swift_Mailer($transport);
    }
}