describe('Packages page', () => {
  beforeEach(() => {
    cy.visit('/packages', { waitForApi: true })
  })

  it('shows packages', () => {
    cy.get('[data-test=package-link]').should('have.length.gt', 20)
    cy.get('[data-test=package-link]').should('have.attr', 'href')
  })

  it('filter packages', () => {
    cy.get('[data-test=packages-search]').type('pacman', { delay: 200 })
    cy.location().should((loc) => {
      expect(loc.search).to.eq('?search=pacman')
    })
    cy.get('[data-test=packages-filter-repository] option:selected').should('have.text', '')
    cy.get('[data-test=packages-filter-architecture]').should('not.exist')
    cy.get('[data-test=package-link]').contains('pacman')
  })

  it('loads next page', () => {
    cy.get('[data-test=package-link]').first().then($el => {
      const firstPackage = $el.text()
      cy.get('[data-test=next]').click()
      cy.get('[data-test=package-link]').first().invoke('text').should('not.contain', firstPackage)
    })
  })

  it('loads previous page', () => {
    cy.get('[data-test=package-link]').first().then($el => {
      const firstPackage = $el.text()
      cy.get('[data-test=next]').click()
      cy.get('[data-test=previous]').click()
      cy.get('[data-test=package-link]').first().invoke('text').should('be.equal', firstPackage)
    })
  })

  it('filters by repository', () => {
    cy.get('[data-test=packages-filter-repository]').select('core')
    cy.location().should((loc) => {
      expect(loc.search).to.eq('?repository=core')
    })
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.wait(2000)
    cy.get('[data-test=package-repository-link]').each($el => {
      expect($el.text()).to.eq('core')
    })
  })

  it('navigates to repository and architecture filter', () => {
    cy.get('[data-test=packages-search]').type('pacman', { delay: 200 })
    cy.get('[data-test=package-link]').contains('pacman')

    cy.get('[data-test=package-repository-link]').first().click()

    cy.location().should((loc) => {
      expect(loc.search).to.match(/\?repository=.*&architecture=.*/)
    })
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.wait(500)
    cy.get('[data-test=packages-search]').should('have.value', '')
    cy.get('[data-test=package-link]').should('have.length.gt', 0)
    cy.get('[data-test=packages-filter-repository] option:selected').should('exist')
    cy.get('[data-test=packages-filter-architecture] option:selected').should('have.text', 'x86_64')
  })
})
