<?php

class Shopware_Controllers_Backend_FatchipCTApilog extends Shopware_Controllers_Backend_ExtJs
{

  /**
   *  get logs action, loads log entries with paging
   */
    public function getApilogsAction()
    {
        $start = $this->Request()->get('start');
        $limit = $this->Request()->get('limit');

      //Get the value itself
        if ($this->Request()->get('filter')) {
            $filter      = $this->Request()->get('filter');
            $filter      = $filter[count($filter) - 1];
            $filterValue = $filter['value'];
        }

        $builder = $this->getLogQuery();

      //order data
        $order = (array) $this->Request()->getParam('sort', array());
        if ($order) {
            foreach ($order as $ord) {
                $builder->addOrderBy('log.' . $ord['property'], $ord['direction']);
            }
        } else {
            $builder->addOrderBy('log.creationDate', 'DESC');
        }

        if ($filterValue) {
            $builder->where('log.merchantId = ?1')->setParameter(1, $filterValue);
        }

        $builder->setFirstResult($start)->setMaxResults($limit);

        $result = $builder->getQuery()->getArrayResult();

        $result = $this->addArrayRequestResponse($result);

        $total = Shopware()->Models()->getQueryCount($builder->getQuery());

        $this->View()->assign(array('success' => true, 'data'    => $result, 'total'   => $total));
    }

  /**
   * grid data action, returns api call details
   */
    public function getGridDataAction()
    {
        $type = $this->Request()->get('type');

        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select('log.id as id', 'log.requestDetails as requestDetails', 'log.responseDetails as responseDetails')
            ->from('Shopware\CustomModels\FatchipCTApilog\FatchipCTApilog', 'log')
            ->where('log.id = ?1')
            ->setParameter(1, $this->Request()->get('id'));

        $result = $builder->getQuery()->getArrayResult();

        $result = $this->addArrayRequestResponse($result);

        $total = Shopware()->Models()->getQueryCount($builder->getQuery());
        $this->View()->assign(array('success' => true, 'data'    => $result[0][$type . 'Array'], 'total'   => $total));
    }

  /**
   * helper method, extracts response/request data
   *
   * @param string $result
   * @return array
   */
    protected function addArrayRequestResponse($result)
    {
        if (!empty($result)) {
            foreach ($result as $key => $entry) {
                $request = [];
                $response = [];

                $dataRequest = json_decode($entry['requestDetails']);

                foreach ($dataRequest as $reqKey => $value) {
                    $request[] = ['name'  => $reqKey, 'value' => $value];
                }

                $dataResponse = json_decode($entry['responseDetails']);
                foreach ($dataResponse as $respKey => $value) {
                    $response[] = ['name'  => $respKey, 'value' => $value];
                }

                $result[$key]['requestArray']  = $request;
                $result[$key]['responseArray'] = $response;
            }
        }
        return $result;
    }

  /**
   * controller action, returns log data
   */
    public function controllerAction()
    {
        $start = $this->Request()->get('start');
        $limit = $this->Request()->get('limit');

      //order data
        $order = (array) $this->Request()->getParam('sort', array());
      //Get the value itself
        if ($this->Request()->get('filter')) {
            $filter      = $this->Request()->get('filter');
            $filter      = $filter[count($filter) - 1];
            $filterValue = $filter['value'];
        }

        $builder = $this->getLogQuery();

        if ($filterValue) {
            $builder->where('log.merchant_id = ?1')->setParameter(1, $filterValue);
        }
        $builder->addOrderBy($order);

        $builder->setFirstResult($start)->setMaxResults($limit);

        $result = $builder->getQuery()->getArrayResult();
        $total  = Shopware()->Models()->getQueryCount($builder->getQuery());


        $this->View()->assign(array('success' => true, 'data'    => $result, 'total'   => $total));
    }

  /**
   * assigns search result data to view object
   */
    public function getSearchResultAction()
    {
        $filters = $this->Request()->get('filter');

        $builder = $this->getLogQuery();

        foreach ($filters as $filter) {
            if ($filter['property'] == 'search' && !empty($filter['value'])) {
                $builder->where($builder->expr()->orx(
                    $builder->expr()->like(
                        'log.requestDetails',
                        $builder->expr()->literal(
                            '%' . $filter['value'] . '%'
                        )
                    ),
                    $builder->expr()->like('log.responseDetails', $builder->expr()->literal(
                        '%' . $filter['value'] . '%'
                    ))
                ));
            } elseif ($filter['property'] == 'searchtrans' && !empty($filter['value'])) {
                $builder->where($builder->expr()->orx($builder->expr()->like('log.responseDetails', $builder->expr()->literal(
                    '%PayID=' . $filter['value'] . '%'
                ))));
            }
        }

        $builder->setMaxResults(20);
        $result = $builder->getQuery()->getArrayResult();
        $total  = Shopware()->Models()->getQueryCount($builder->getQuery());

        $this->View()->assign(array('success' => true, 'data'    => $result, 'total'   => $total));
    }

    public function getLogQuery()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        $builder->select(
            'log.id as id',
            'log.request as request',
            'log.response as response',
            'log.paymentName as paymentName',
            'log.payId as payId',
            'log.transId as transId',
            'log.xId as xId',
            'log.creationDate as creationDate',
            'log.requestDetails as requestDetails',
            'log.responseDetails as responseDetails'
        )->from('Shopware\CustomModels\FatchipCTApilog\FatchipCTApilog', 'log');

        return $builder;
    }
}
