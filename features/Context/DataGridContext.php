<?php

namespace Context;

use Behat\Behat\Context\Step\Then;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use SensioLabs\Behat\PageObjectExtension\Context\PageObjectAwareInterface;
use SensioLabs\Behat\PageObjectExtension\Context\PageFactory;
use Context\Page\Base\Grid;

/**
 * Feature context for the datagrid related steps
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class DataGridContext extends RawMinkContext implements PageObjectAwareInterface
{
    /**
     * @var \SensioLabs\Behat\PageObjectExtension\Context\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Context\Page\Base\Grid
     */
    protected $datagrid;

    /**
     * @param PageFactory $pageFactory
     */
    public function setPageFactory(PageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
        $this->datagrid = $pageFactory->createPage('Base\Grid');
    }

    /**
     * @param integer $count
     *
     * @Given /^the grid should contain (\d+) elements?$/
     */
    public function theGridShouldContainElement($count)
    {
        assertEquals(
            intval($count),
            $actualCount = $this->datagrid->getToolbarCount(),
            sprintf('Expecting to see %d record(s) in the datagrid, actually saw %d', $count, $actualCount)
        );

        assertEquals(
            intval($count),
            $actualCount = $this->datagrid->countRows(),
            sprintf('Expecting to see %d row(s) in the datagrid, actually saw %d.', $count, $actualCount)
        );
    }

    /**
     * @param string $action
     * @param string $value
     * @param string $currency
     *
     * @When /^I filter per price (>|>=|=|<|<=) "([^"]*)" and currency "([^"]*)"$/
     */
    public function iFilterPerPrice($action, $value, $currency)
    {
        $this->datagrid->filterPerPrice($action, $value, $currency);
        $this->wait();
    }

    /**
     * @param string $code
     *
     * @Given /^I filter per category "([^"]*)"$/
     */
    public function iFilterPerCategory($code)
    {
        $category = $this->getFixturesContext()->getCategory($code);
        $this->getCurrentPage()->clickCategoryFilterLink($category);
        $this->wait();
    }

    /**
     * @Given /^I filter per unclassified category$/
     */
    public function iFilterPerUnclassifiedCategory()
    {
        $this->wait();
        $this->getCurrentPage()->clickUnclassifiedCategoryFilterLink();
        $this->wait();
    }

    /**
     * @param string $code
     *
     * @Given /^I filter per family ([^"]*)$/
     */
    public function iFilterPerFamily($code)
    {
        $this->datagrid->filterPerFamily($code);
        $this->wait();
    }

    /**
     * @param string $code
     *
     * @Given /^I filter per channel ([^"]*)$/
     */
    public function iFilterPerChannel($code)
    {
        $this->datagrid->filterPerChannel($code);
        $this->wait();
    }

    /**
     * @param string    $code
     * @param TableNode $table
     *
     * @Then /^the row "([^"]*)" should contain:$/
     */
    public function theRowShouldContain($code, TableNode $table)
    {
        foreach ($table->getHash() as $data) {
            $this->assertColumnContainsValue($code, $data['column'], $data['value']);
        }
    }

    /**
     * @param string $row
     * @param string $column
     * @param string $expectation
     *
     * @throws ExpectationException
     */
    protected function assertColumnContainsValue($row, $column, $expectation)
    {
        $column = strtoupper($column);
        $actual = $this->datagrid->getColumnValue($column, $row);

        if ($expectation !== $actual) {
            throw $this->createExpectationException(
                sprintf(
                    'Expecting column "%s" to contain "%s", got "%s"',
                    $column,
                    $expectation,
                    $actual
                )
            );
        }
    }

    /**
     * @param string $filters
     *
     * @Then /^I should see the filters? (.*)$/
     */
    public function iShouldSeeTheFilters($filters)
    {
        $filters = $this->getMainContext()->listToArray($filters);
        foreach ($filters as $filter) {
            $filterNode = $this->datagrid->getFilter($filter);
            if (!$filterNode->isVisible()) {
                $this->createExpectationException(
                    sprintf('Filter "%s" should be visible', $filter)
                );
            }
        }
    }

    /**
     * @param string $filters
     *
     * @Then /^I should not see the filters? (.*)$/
     */
    public function iShouldNotSeeTheFilters($filters)
    {
        $filters = $this->getMainContext()->listToArray($filters);
        foreach ($filters as $filter) {
            $filterNode = $this->datagrid->getFilter($filter);
            if ($filterNode->isVisible()) {
                $this->createExpectationException(
                    sprintf('Filter "%s" should not be visible', $filter)
                );
            }
        }
    }

    /**
     * @param string $filterName
     *
     * @Then /^I make visible the filter "([^"]*)"$/
     */
    public function iMakeVisibleTheFilter($filterName)
    {
        $this->datagrid->showFilter($filterName);
    }

    /**
     * @param string $filterName
     *
     * @Then /^I hide the filter "([^"]*)"$/
     */
    public function iHideTheFilter($filterName)
    {
        $this->datagrid->hideFilter($filterName);
    }

    /**
     * @param string $columns
     *
     * @Then /^I should see the columns? (.*)$/
     */
    public function iShouldSeeTheColumns($columns)
    {
        $columns = $this->getMainContext()->listToArray($columns);

        $expectedColumns = count($columns);
        $countColumns    = $this->datagrid->countColumns();
        if ($expectedColumns !== $countColumns) {
            throw $this->createExpectationException(
                sprintf('Expected %d columns but contains %d', $expectedColumns, $countColumns)
            );
        }

        $expectedPosition = 0;
        foreach ($columns as $column) {
            $position = $this->datagrid->getColumnPosition($column);
            if ($expectedPosition++ !== $position) {
                throw $this->createExpectationException("The columns are not well ordered");
            }
        }
    }

    /**
     * @param string $order
     * @param string $columnName
     *
     * @Then /^the rows should be sorted (ascending|descending) by (.*)$/
     */
    public function theRowsShouldBeSortedBy($order, $columnName)
    {
        $columnName = strtoupper($columnName);

        if (!$this->datagrid->isSortedAndOrdered($columnName, $order)) {
            $this->createExpectationException(
                sprintf('The rows are not sorted %s by column %s', $order, $columnName)
            );
        }
    }

    /**
     * @param string $actionName
     * @param string $element
     *
     * @When /^I (delete) the "([^"]*)" job$/
     * @Given /^I click on the "([^"]*)" action of the row which contains "([^"]*)"$/
     */
    public function iClickOnTheActionOfTheRowWhichContains($actionName, $element)
    {
        $action = ucfirst(strtolower($actionName));
        $this->datagrid->clickOnAction($element, $action);
    }

    /**
     * @param string $columns
     *
     * @Then /^I should be able to sort the rows by (.*)$/
     *
     * @return Then[]
     */
    public function iShouldBeAbleToSortTheRowsBy($columns)
    {
        $steps = array(
            new Then(sprintf('the rows should be sortable by %s', $columns))
        );
        $columns = $this->getMainContext()->listToArray($columns);

        foreach ($columns as $column) {
            $steps[] = new Then(sprintf('I sort by "%s" value ascending', $column));
            $steps[] = new Then(sprintf('the rows should be sorted ascending by %s', $column));
            $steps[] = new Then(sprintf('I sort by "%s" value descending', $column));
            $steps[] = new Then(sprintf('the rows should be sorted descending by %s', $column));
        }

        return $steps;
    }

    /**
     * @param string $columnName
     * @param string $order
     *
     * @When /^I sort by "(.*)" value (ascending|descending)$/
     */
    public function iSortByValue($columnName, $order = 'ascending')
    {
        $this->datagrid->sortBy($columnName, $order);
        $this->wait();
    }

    /**
     * @param string $columns
     *
     * @Then /^the rows should be sortable by (.*)$/
     */
    public function theRowsShouldBeSortableBy($columns)
    {
        $columns = $this->getMainContext()->listToArray($columns);

        try {
            foreach ($columns as $columnName) {
                $this->datagrid->getColumnSorter($columnName);
            }
        } catch (\InvalidArgumentException $e) {
            $this->createExpectationException($e->getMessage());
        }
    }

    /**
     * @param string $elements
     *
     * @throws ExpectationException
     *
     * @Then /^I should see products? (.*)$/
     * @Then /^I should see attributes? (?!(.*)in group )(.*)$/
     * @Then /^I should see channels? (.*)$/
     * @Then /^I should see locales? (.*)$/
     * @Then /^I should see (?:import|export) profiles? (.*)$/
     * @Then /^I should see (?:(?:entit|currenc)(?:y|ies)) (.*)$/
     * @Then /^I should see groups? (.*)$/
     * @Then /^I should see associations? (.*)$/
     */
    public function iShouldSeeEntities($elements)
    {
        $elements = $this->getMainContext()->listToArray($elements);

        foreach ($elements as $element) {
            if (!$this->datagrid->getRow($element)) {
                throw $this->createExpectationException(sprintf('Entity "%s" not found', $element));
            }
        }
    }

    /**
     * @param array $entities
     *
     * @Then /^I should not see products? (.*)$/
     * @Then /^I should not see attributes? (?!(.*)in group )(.*)$/
     * @Then /^I should not see channels? (.*)$/
     * @Then /^I should not see locales? (.*)$/
     * @Then /^I should not see (?:import|export) profiles? (.*)$/
     * @Then /^I should not see (?:(?:entit|currenc)(?:y|ies)) (.*)$/
     * @Then /^I should not see groups? (.*)$/
     * @Then /^I should not see associations? (.*)$/
     */
    public function iShouldNotSeeEntities($entities)
    {
        $entities = $this->getMainContext()->listToArray($entities);

        foreach ($entities as $entity) {
            try {
                $this->datagrid->getRow($entity);
                $this->createExpectationException(
                    sprintf('Entity "%s" should not be seen', $entity)
                );
            } catch (\InvalidArgumentException $e) {
                // here we must catch an exception because the row is not found
                continue;
            }
        }
    }

    /**
     * @param string $filterName
     * @param string $value
     *
     * @Then /^I filter by "([^"]*)" with value "([^"]*)"$/
     */
    public function iFilterBy($filterName, $value)
    {
        $this->datagrid->filterBy($filterName, $value);
        $this->wait();
    }

    /**
     * @param string $filterName
     * @param string $operatorName
     * @param string $value
     *
     * @Then /^I filter by "([^"]*)" with operator "([^"]*)" and value "([^"]*)"$/
     */
    public function iFilterByWithOperator($filterName, $operatorName, $value)
    {
        $operators = array(
            'contains' => Grid::FILTER_CONTAINS,
            'does not contain' => Grid::FILTER_DOES_NOT_CONTAIN,
            'is equal to' => Grid::FILTER_IS_EQUAL_TO,
            'starts with' => Grid::FILTER_STARTS_WITH,
            'ends with' => Grid::FILTER_ENDS_WITH
        );

        if (!isset($operators[$operatorName])) {
            throw new \InvalidArgumentException("Operator $operatorName is unknown.");
        }

        $operator = $operators[$operatorName];

        $this->datagrid->filterBy($filterName, $value, $operator);
        $this->wait();
    }

    /**
     * @param string $row
     *
     * @When /^I click on the "([^"]*)" row$/
     */
    public function iClickOnTheRow($row)
    {
        $this->datagrid->getRow($row)->click();
        $this->wait();
    }

    /**
     * @param string $rows
     *
     * @throws ExpectationException
     *
     * @When /^I check the rows? "([^"]*)"$/
     */
    public function iCheckTheRows($rows)
    {
        $rows = $this->getMainContext()->listToArray($rows);

        foreach ($rows as $row) {
            $gridRow = $this->datagrid->getRow($row);
            $checkbox = $gridRow->find('css', 'input[type="checkbox"][data-identifier]:not(:disabled)');

            if (!$checkbox) {
                throw $this->createExpectationException(sprintf('Unable to find a checkbox for row %s', $row));
            }

            $checkbox->check();
        }
    }

    /**
     * @param string $rows
     *
     * @throws ExpectationException
     *
     * @Then /^the rows? "([^"]*)" should be checked$/
     */
    public function theRowShouldBeChecked($rows)
    {
        $rows = $this->getMainContext()->listToArray($rows);

        foreach ($rows as $row) {
            $gridRow = $this->datagrid->getRow($row);
            $checkbox = $gridRow->find('css', 'input[type="checkbox"][data-identifier]:not(:disabled)');

            if (!$checkbox) {
                throw $this->createExpectationException(sprintf('Unable to find a checkbox for row %s', $row));
            }

            if (!$checkbox->isChecked()) {
                throw $this->createExpectationException(sprintf('Expecting row %s to be checked', $row));
            }
        }
    }

    /**
     * @Then /^I reset the grid$/
     */
    public function iResetTheGrid()
    {
        $this->datagrid->clickOnResetButton();
        $this->wait();
    }

    /**
     * @Then /^I refresh the grid$/
     */
    public function iRefrestTheGrid()
    {
        $this->datagrid->clickOnRefreshButton();
        $this->wait();
    }

    /**
     * @Then /^I click back to grid$/
     */
    public function iClickBackToGrid()
    {
        $this->getSession()->getPage()->clickLink('Back to grid');
        $this->wait();
    }

    /**
     * Create an expectation exception
     *
     * @param string $message
     *
     * @return ExpectationException
     */
    private function createExpectationException($message)
    {
        return $this->getMainContext()->createExpectationException($message);
    }

    /**
     * Wait
     *
     * @param integer $time
     * @param string  $condition
     */
    private function wait($time = 10000, $condition = null)
    {
        $this->getMainContext()->wait($time, $condition);
    }

    /**
     * @return \Behat\Behat\Context\ExtendedContextInterface
     */
    private function getNavigationContext()
    {
        return $this->getMainContext()->getSubcontext('navigation');
    }

    /**
     * @return \Behat\Behat\Context\ExtendedContextInterface
     */
    private function getFixturesContext()
    {
        return $this->getMainContext()->getSubcontext('fixtures');
    }

    /**
     * @return \SensioLabs\Behat\PageObjectExtension\PageObject\Page
     */
    public function getCurrentPage()
    {
        return $this->getNavigationContext()->getCurrentPage();
    }
}
