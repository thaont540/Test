<?php
/**
 * Company Model.
 */
class Company
{
    /**
     * Companies cost.
     *
     * @var array
     */
    public $companiesCost;

    /**
     * Companies price.
     *
     * @var array
     */
    public $companiesPrice;

    /**
     * Companies data from API.
     *
     * @var array
     */
    public $companies;

    /**
     * Constructor.
     * Initial all companies.
     *
     * @return void
     */
    public function __construct()
    {
        $this->companies = $this->getAll();
    }

    /**
     * Get all companies from API.
     *
     * @return array
     */
    private function getAll()
    {
        $url = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';
        $res = file_get_contents($url);

        return json_decode($res, true);
    }

    /**
     * Calculate company cost.
     * Company cost = sum all price of children and company price itself.
     *
     * @param $parentId
     * @param int $cost
     * @return int
     */
    public function getCompanyChildCost($parentId, $cost = 0)
    {
        foreach ($this->companies as $company) {
            if ($company['parentId'] == $parentId) {
                $cost += $this->companiesPrice[$company['id']] ?? 0;
                $this->getCompanyChildCost($company['id'], $cost);
            }
        }

        return $cost;
    }

    /**
     * Build company tree.
     * It's also inject company cost for each company.
     *
     * @param $parentId
     * @return array
     */
    public function buildTree($parentId)
    {
        $treeBranch = [];

        foreach ($this->companies as $company) {
            if (isset($company['parentId']) && $company['parentId'] == $parentId) {
                $childCompanies = $this->buildTree($company['id']);
                if ($childCompanies) {
                    for ($i = 0; $i < count($childCompanies); $i++) {
                        $childCompanies[$i]['cost'] = $this->companiesCost[$childCompanies[$i]['id']];
                        if (isset($childCompanies[$i]['createdAt'])) {
                            unset($childCompanies[$i]['createdAt']);
                        }

                        unset($childCompanies[$i]['parentId']);
                    }

                    $company['cost'] = $this->companiesCost[$company['id']];
                    $company['children'] = $childCompanies;
                }

                $branch = [
                    'id' => $company['id'] ?? null,
                    'name' => $company['name'] ?? null,
                    'cost' => $company['cost'] ?? null,
                ];

                if (isset($company['children'])) {
                    $branch['children'] = $company['children'];

                }

                $treeBranch[] = $branch;
            }
        }

        return $treeBranch;
    }
}

/**
 * Travel Model.
 */
class Travel
{
    /**
     * Travels data from API.
     *
     * @var array
     */
    public $travels;

    /**
     * Constructor.
     * Initial all travels.
     *
     * @return void
     */
    public function __construct()
    {
        $this->travels = $this->getAll();
    }

    /**
     * Get all travels from API.
     *
     * @return array
     */
    private function getAll()
    {
        $url = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';
        $res = file_get_contents($url);

        return json_decode($res, true);
    }

    /**
     * Calculate company price.
     * Company price = total all price in each travel.
     *
     * @return array
     */
    public function getCompanyPrices()
    {
        $companiesPrice = [];
        for ($i = 0; $i < count($this->travels); $i++) {
            if (isset($companiesPrice[$this->travels[$i]['companyId']])) {
                $companiesPrice[$this->travels[$i]['companyId']] += $this->travels[$i]['price'];
            } else {
                $companiesPrice[$this->travels[$i]['companyId']] = $this->travels[$i]['price'];
            }
        }

        return $companiesPrice;
    }
}

/**
 * Test class.
 */
class TestScript
{
    /**
     * Execute test code.
     *
     * @return void
     */
    public function execute()
    {
        $start = microtime(true);
        $cModel = new Company();
        $tModel = new Travel();
        $cModel->companiesPrice = $tModel->getCompanyPrices();
        // initial price for each company from travels, company price = total all price in each travel

        $companiesCost = [];
        foreach ($cModel->companies as $cpm) {
            $companiesCost[$cpm['id']] = $cModel->getCompanyChildCost($cpm['id']) + ($cModel->companiesPrice[$cpm['id']] ?? 0);
            // initial cost for each company, cost = sum all price of children and company price itself
        }

        $cModel->companiesCost = $companiesCost;

        $companiesTree = $cModel->buildTree("0");
        // build company tree, also insert "cost" for each

        echo json_encode($companiesTree);

        echo "\nTotal time: " .  (microtime(true) - $start);
    }
}

(new TestScript())->execute();
