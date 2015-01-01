<?php namespace PHPSocketMaster;


class newClient extends SocketEventReceptor
{

	private $name = 'Noname';
	private $requested = false;
	public $id;
    private $ready = false;
    private $requestName = false;
    private $position = 0;
    private $started = false;

	public function onError()
	{
		echo '> oOps error in client: '.$this->name;
		// borramos el cliente con el error
		ServerManager::DeleteClient($this->id); 
	}

	public function onConnect()
	{
        if($this->ready == false)
        {
		    echo '> New client...';
            $this->ready = true;
        } else {
            $this->requestName = true;
            $this->getBridge()->send(json_encode(array('type' => 'system', 'msg' => 'getNick')));
        }
	}

	public function onDisconnect()
	{
		echo '> disconnect client: '.$this->name;
		ServerManager::DeleteClient($this->id);
        \CorePoker::disconnected($this->id);
	}

	public function onReceiveMessage($message)
	{
		
		// fix for windows sockets message
		$message = is_array($message) ? $message[0] : $message;
		// que es lo que nos mandan?
        if($this->requestName)
        {
            if($this->started)
            {
                \CorePoker::analize($message, $this->id);
            } else {
                $this->name = $message['payload'];
                $this->sitdown();
                $this->started = true;
            }
        }
	}
    
    public function sitdown()
    {
        // sentarme
        $this->position = \CorePoker::sit(base64_encode($this->id.$this->name), $this->name, $this->id);
        // enviarme a mi la posición
        $this->getBridge()->send(json_encode(array('type' => 'system', 'msg' => 'meClient', 'data' => $this->position)));
        // enviarme a mi las fichas que tengo
        $this->getBridge()->send(json_encode(array('type' => 'system', 'msg' => 'fichas', 'data' => \CorePoker::me(base64_encode($this->id.$this->name))->fichas)));
        // enviarme a mi la posición
        $this->getBridge()->send(json_encode(array('type' => 'system', 'msg' => 'clients', 'data' => json_encode(\CorePoker::getClients()))));
        // enviar a todos mi posición
        ServerManager::Resend(json_encode(array('type' => 'system', 'msg' => 'newClient', 'data' => $this->position, 'nick' => $this->name, 'fichas' => \CorePoker::me(base64_encode($this->id.$this->name))->fichas)));
        
        // verificamos si se puede comenzar, y en caso de que se pueda comenzar, mezclamos, repartimos, pedimos ciegas, etc.
        if(\CorePoker::continuable())
            \CorePoker::init();
        else
             ServerManager::Resend(json_encode(array('type' => 'notify', 'msg' => 'Se est&aacute;n esperando m&aacute;s jugadores')));
    }
    
    public function onSendRequest(&$cancel, $message) 
    {
        //...
    }
    
    public function onSendComplete($message) 
    {
        //... 
    }
}