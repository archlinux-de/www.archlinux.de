describe('Releases page', () => {
  beforeEach(() => {
    cy.visit('/releases')
  })

  it('shows releases', () => {
    cy.get('[data-test=release-link]').should('have.length.gt', 20)
    cy.get('[data-test=release-link]').should('have.attr', 'href')
  })

  it('filter releases', () => {
    cy.get('[data-test=releases-search]').type('2019', { delay: 200 })
    cy.location().should((loc) => {
      expect(loc.search).to.eq('?search=2019')
    })
    cy.get('[data-test=release-link]').contains('2019')
    cy.get('[data-test=release-link]').should('have.length.lt', 20)
  })

  it('loads next page', () => {
    cy.get('[data-test=release-link]').first().then($el => {
      const firstRelease = $el.text()
      cy.get('[data-test=next]').click()
      cy.get('[data-test=release-link]').first().invoke('text').should('not.contain', firstRelease)
    })
  })

  it('loads previous page', () => {
    cy.get('[data-test=release-link]').first().then($el => {
      const firstRelease = $el.text()
      cy.get('[data-test=next]').click()
      cy.get('[data-test=previous]').click()
      cy.get('[data-test=release-link]').first().invoke('text').should('be.equal', firstRelease)
    })
  })
})
