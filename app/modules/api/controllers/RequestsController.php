<?php
namespace Modules\Api\Controllers;

use \Entity\Station;
use \Entity\StationRequest;

class RequestsController extends BaseController
{
    public function listAction()
    {
        $station = $this->_getStation();

        if (!$station)
            return $this->returnError('Station not found!');

        $requestable_media = $this->em->createQuery('SELECT sm, s, sp 
            FROM Entity\StationMedia sm JOIN sm.song s LEFT JOIN sm.playlists sp
            WHERE sm.station_id = :station_id AND sp.id IS NOT NULL')
            ->setParameter('station_id', $station->id)
            ->getArrayResult();

        $result = array();

        foreach($requestable_media as $media_row)
        {
            $result_row = array(
                'song' => \Entity\Song::api($media_row['song']),
                'request_song_id' => $media_row['id'],
                'request_url' => $this->url->routeFromHere(['action' => 'submit', 'song_id' => $media_row['id']]),
            );
            $result[] = $result_row;
        }

        return $this->returnSuccess($result);
    }

    public function submitAction()
    {
        $station = $this->_getStation();

        if (!$station)
            return $this->returnError('Station not found!');

        $song = $this->getParam('song_id');

        try
        {
            $this->em->getRepository(StationRequest::class)->submit($station, $song, $this->authenticate());

            return $this->returnSuccess('Request submitted successfully.');
        }
        catch(\App\Exception $e)
        {
            return $this->returnError($e->getMessage());
        }
    }

    protected function _getStation()
    {
        $station = $this->getParam('station');

        if (is_numeric($station))
        {
            $id = (int)$station;
            $record = $this->em->getRepository(Station::class)->find($id);
        }
        else
        {
            $record = $this->em->getRepository(Station::class)->findByShortCode($this->getParam('station'));
        }

        if (!($record instanceof Station) || $record->deleted_at)
            return null;

        return $record;
    }
}