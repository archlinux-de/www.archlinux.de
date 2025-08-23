describe('News page', () => {
  beforeEach(() => {
    cy.visit('/news', { waitForApi: true })
  })

  it('shows news', () => {
    cy.get('[data-test=news-item-link]').should('have.length.gt', 20)
    cy.get('[data-test=news-item-link]').should('have.attr', 'href')
  })

  it('filter news', () => {
    cy.get('[data-test=news-search]').type('Canterbury', { delay: 200 })
    cy.location().should((loc) => {
      expect(loc.search).to.eq('?search=Canterbury')
    })
    cy.get('[data-test=news-item-link]').contains('Canterbury')
    cy.get('[data-test=news-item-link]').should('have.length.lt', 20)
  })

  it('loads next page', () => {
    cy.get('[data-test=news-item-link]').first().then($el => {
      const firstNewsItem = $el.text()
      cy.get('[data-test=next]').click()
      cy.get('[data-test=news-item-link]').first().invoke('text').should('not.contain', firstNewsItem)
    })
  })

  it('loads previous page', () => {
    cy.get('[data-test=news-item-link]').first().then($el => {
      const firstNewsItem = $el.text()
      cy.get('[data-test=next]').click()
      cy.get('[data-test=previous]').click()
      cy.get('[data-test=news-item-link]').first().invoke('text').should('be.equal', firstNewsItem)
    })
  })
})
