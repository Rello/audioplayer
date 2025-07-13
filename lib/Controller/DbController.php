<?php
namespace OCA\audioplayer\Controller;

use OCA\audioplayer\DB\DbMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class DbController extends Controller
{
    private DbMapper $mapper;

    public function __construct(string $appName, IRequest $request, DbMapper $mapper)
    {
        parent::__construct($appName, $request);
        $this->mapper = $mapper;
    }

    /**
     * @NoAdminRequired
     */
    public function resetMediaLibrary(): JSONResponse
    {
        $this->mapper->resetMediaLibrary();
        return new JSONResponse(['status' => 'success']);
    }
}
