describe('Mirrors page', () => {
  beforeEach(() => {
    cy.visit('/mirrors')
  })

  it('shows mirrors', () => {
    cy.get('[data-test=mirror-link]').should('have.length.gt', 20)
    cy.get('[data-test=mirror-link]').should('have.attr', 'href')
  })

  it('filter mirrors', () => {
    cy.get('[data-test=mirrors-search]').type('Cologne')
    cy.location().should((loc) => {
      expect(loc.search).to.eq('?search=Cologne')
    })
    cy.get('[data-test=mirror-link]').contains('cologne')
    cy.get('[data-test=mirror-link]').should('have.length.lt', 20)
  })

  it('loads next page', () => {
    cy.get('[data-test=mirror-link]').first().then($el => {
      const firstMirror = $el.text()
      cy.get('[data-test=next]').click()
      cy.get('[data-test=mirror-link]').first().invoke('text').should('not.contain', firstMirror)
    })
  })

  it('loads previous page', () => {
    cy.get('[data-test=mirror-link]').first().then($el => {
      const firstMirror = $el.text()
      cy.get('[data-test=next]').click()
      cy.get('[data-test=previous]').click()
      cy.get('[data-test=mirror-link]').first().invoke('text').should('be.equal', firstMirror)
    })
  })
})
