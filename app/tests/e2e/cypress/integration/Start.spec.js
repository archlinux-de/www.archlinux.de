describe('Start page', () => {
  it('shows the welcome message', () => {
    cy.visit('/')
    cy.contains('h1', 'Willkommen bei Arch Linux')
  })

  it('shows the latest news', () => {
    cy.visit('/')
    cy.get('[data-test=news-item]').should('have.length', 6)
    cy.get('[data-test=news-item] h2').should('be.visible').should('not.be.empty')
    cy.get('[data-test=news-item] h2 a').should('have.attr', 'href')
    cy.get('[data-test=news-item] [data-test=news-item-last-modified]').should('be.visible').should('not.be.empty')
    cy.get('[data-test=news-item] [data-test=news-item-description]').should('be.visible').should('not.be.empty')
  })

  it('shows the latest packages', () => {
    cy.visit('/')
    cy.get('[data-test=recent-package]').should('have.length', 20)
    cy.get('[data-test=recent-package] [data-test=recent-package-name]').should('be.visible').should('not.be.empty')
    cy.get('[data-test=recent-package] [data-test=recent-package-name] a').should('have.attr', 'href')
    cy.get('[data-test=recent-package] [data-test=recent-package-version]').should('be.visible').should('not.be.empty')
  })

  it('shows package search suggestions', () => {
    cy.visit('/')
    cy.get('#searchfield').should('be.visible').type('lin')
    cy.get('#searchfield-list option').should('have.length', 10)
  })
})
