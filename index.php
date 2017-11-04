<?

	include "whmcsLib.php";

	$w = new WhmcsLib('GetClients', []);

	$w->render($w->response);
