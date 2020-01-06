<?php

/**
 * The Computop Shopware Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The Computop Shopware Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Computop Shopware Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.6, 7.0 , 7.1
 *
 * @category   Payment
 * @package    FatchipCTPayment
 * @subpackage Controllers/Backend
 * @author     FATCHIP GmbH <support@fatchip.de>
 * @copyright  2018 Computop
 * @license    <http://www.gnu.org/licenses/> GNU Lesser General Public License
 * @link       https://www.computop.com
 */

/**
 * Class Shopware_Controllers_Backend_FatchipCTApilog
 *
 *  gets api log entries from the database and assigns them to the view.
 */
class Shopware_Controllers_Backend_FatchipCTApilog extends Shopware_Controllers_Backend_ExtJs
{

    /**
     * reads api log data for usage in apilog list view.
     *
     * assigns found data to view
     *
     * @return void
     */
    public function getApilogsAction()
    {
        $start = $this->Request()->get('start');
        $limit = $this->Request()->get('limit');

        //Get search value itself
        if ($this->Request()->get('filter')) {
            $filter = $this->Request()->get('filter');
            $filter = $filter[count($filter) - 1];
            $filterValue = $filter['value'];
        }

        $builder = $this->getLogQuery();

        //order data
        $order = (array)$this->Request()->getParam('sort', array());
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

        $this->View()->assign(array('success' => true, 'data' => $result, 'total' => $total));
    }

    /**
     * sets request response details for use in backend detail view.
     *
     * assigns details data to view
     *
     *
     * @return void
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

        $resultArray = $this->addArrayRequestResponse($result);

        $total = Shopware()->Models()->getQueryCount($builder->getQuery());
        $this->View()->assign(array('success' => true, 'data' => $resultArray[0][$type . 'Array'], 'total' => $total));
    }

    /**
     * helper method, extracts response/request data
     *
     * @param string $result
     * @return array|string
     */
    protected function addArrayRequestResponse($result)
    {
        if (!empty($result)) {
            foreach ($result as $key => $entry) {
                $request = [];
                $response = [];

                $dataRequest = json_decode($entry['requestDetails']);

                foreach ($dataRequest as $reqKey => $value) {
                    $request[] = ['name' => $reqKey, 'value' => $value];
                }

                $dataResponse = json_decode($entry['responseDetails']);
                foreach ($dataResponse as $respKey => $value) {
                    $response[] = ['name' => $respKey, 'value' => $value];
                }

                $result[$key]['requestArray'] = $request;
                $result[$key]['responseArray'] = $response;
            }
        }
        return $result;
    }

    /**
     * controller action, returns log data
     *
     * @return void
     */
    public function controllerAction()
    {
        $start = $this->Request()->get('start');
        $limit = $this->Request()->get('limit');

        //order data
        $order = (array)$this->Request()->getParam('sort', array());
        //Get the value itself
        if ($this->Request()->get('filter')) {
            $filter = $this->Request()->get('filter');
            $filter = $filter[count($filter) - 1];
            $filterValue = $filter['value'];
        }

        $builder = $this->getLogQuery();

        if ($filterValue) {
            $builder->where('log.merchant_id = ?1')->setParameter(1, $filterValue);
        }
        $builder->addOrderBy($order);

        $builder->setFirstResult($start)->setMaxResults($limit);

        $result = $builder->getQuery()->getArrayResult();
        $total = Shopware()->Models()->getQueryCount($builder->getQuery());


        $this->View()->assign(array('success' => true, 'data' => $result, 'total' => $total));
    }

    /**
     * assigns search result data to view object
     *
     * return void
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
        $total = Shopware()->Models()->getQueryCount($builder->getQuery());

        $this->View()->assign(array('success' => true, 'data' => $result, 'total' => $total));
    }

    /**
     * returns sql base query
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
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
